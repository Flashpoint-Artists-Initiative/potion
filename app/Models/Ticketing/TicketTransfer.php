<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Mail\TicketTransferMail;
use App\Models\Event;
use App\Models\User;
use App\Observers\TicketTransferObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property Event $event
 * @property int $ticketCount
 * @property-read User $user
 */
#[ObservedBy(TicketTransferObserver::class)]
class TicketTransfer extends Model implements ContractsAuditable
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_email',
        'recipient_user_id',
        'completed',
    ];

    protected $with = [
        'purchasedTickets.ticketType',
        'reservedTickets.ticketType',
    ];

    /**
     * @return MorphToMany<PurchasedTicket, $this>
     */
    public function purchasedTickets(): MorphToMany
    {
        return $this->morphedByMany(PurchasedTicket::class, 'ticket', 'ticket_transfer_items');
    }

    /**
     * @return MorphToMany<ReservedTicket, $this>
     */
    public function reservedTickets(): MorphToMany
    {
        return $this->morphedByMany(ReservedTicket::class, 'ticket', 'ticket_transfer_items');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_email', 'email');
    }

    public function scopeInvolvesUser(Builder $query, ?int $userId = null): void
    {
        /** @var User $user */
        $user = $userId ? User::findOrFail($userId) : Auth::user();

        $query->where(function (Builder $query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('recipient_email', $user->email)
                ->orWhere('recipient_user_id', $user->id);
        });
    }

    public function scopeSpecificEvent(Builder $query, ?int $eventId = null): void
    {
        $eventId = $eventId ?? Event::getCurrentEventId();

        if ($eventId) {
            $query->where(function (Builder $query) use ($eventId) {
                $query->whereHas('purchasedTickets.ticketType', function (Builder $query) use ($eventId) {
                    $query->where('event_id', $eventId);
                })->orWhereHas('reservedTickets.ticketType', function (Builder $query) use ($eventId) {
                    $query->where('event_id', $eventId);
                });
            });
        }
    }

    public function scopePending(Builder $query): void
    {
        $query->where('completed', false);
    }

    /**
     * @return Attribute<int,never>
     */
    protected function ticketCount(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->purchasedTickets->count() + $this->reservedTickets->count();
            }
        );
    }

    /**
     * @return Attribute<Event,never>
     */
    protected function event(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($this->purchasedTickets->count() > 0) {
                    $ticket = $this->purchasedTickets->first();
                } else {
                    $ticket = $this->reservedTickets->first();
                }

                /** @var PurchasedTicket|ReservedTicket $ticket */
                return $ticket->event;
            }
        );
    }

    /**
     * Finish the transfer and mark it as completed
     */
    public function complete(): static
    {
        if ($this->completed) {
            return $this;
        }

        $user = User::where('email', $this->recipient_email)->firstOrFail();

        $tickets = $this->purchasedTickets->concat($this->reservedTickets);

        $tickets->each(fn ($ticket) => $ticket->update(['user_id' => $user->id]));

        $this->updateQuietly(['completed' => true, 'recipient_user_id' => $user->id]);

        return $this;
    }

    /**
     * @param  int  $userId  The user initiating the transfer
     * @param  string  $email  The email of the recipient
     * @param  int[]  $purchasedTicketIds
     * @param  int[]  $reservedTicketIds
     */
    public static function createTransfer(int $userId, string $email, array $purchasedTicketIds = [], array $reservedTicketIds = []): TicketTransfer
    {

        $validPurchasedIds = PurchasedTicket::whereIn('id', $purchasedTicketIds)
            ->where('user_id', $userId)
            ->canBeTransferred()
            ->whereHas('event', function (Builder $query) {
                $query->where('end_date', '>', DB::raw('NOW()'));
            })
            ->pluck('id');

        $validReservedIds = ReservedTicket::whereIn('id', $reservedTicketIds)
            ->where('user_id', $userId)
            ->canBeTransferred()
            ->whereHas('event', function (Builder $query) {
                $query->where('end_date', '>', DB::raw('NOW()'));
            })
            ->pluck('id');

        if ($validPurchasedIds->isEmpty() && $validReservedIds->isEmpty()) {
            throw new \RuntimeException('No valid tickets to transfer');
        }

        $transfer = TicketTransfer::create([
            'user_id' => $userId,
            'recipient_email' => $email,
        ]);

        $transfer->purchasedTickets()->attach($validPurchasedIds);
        $transfer->reservedTickets()->attach($validReservedIds);

        $transfer->load(['reservedTickets', 'purchasedTickets']);

        Mail::to($email)
            ->send(new TicketTransferMail($transfer));

        return $transfer;
    }
}
