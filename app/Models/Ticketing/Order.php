<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Models\Event;
use App\Models\User;
use App\Observers\OrderObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property-read User $user
 * @property-read Event $event
 * @property-read Cart $cart
 * @property Carbon $created_at
 * @property-read bool $refundable
 */
#[ObservedBy(OrderObserver::class)]
class Order extends Model implements ContractsAuditable
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_email',
        'user_id',
        'event_id',
        'cart_id',
        'amount_subtotal',
        'amount_total',
        'amount_tax',
        'amount_fees',
        'quantity',
        'stripe_checkout_id',
        'refunded',
        'ticket_data',
    ];

    protected $casts = [
        'ticket_data' => 'array',
        'refunded' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function purchasedTickets(): HasMany
    {
        return $this->hasMany(PurchasedTicket::class, 'order_id');
    }

    /**
     * @return Collection<int, TicketType>
     */
    public function ticketTypes(): Collection
    {
        return once(function () {
            $ids = array_column($this->ticket_data, 'ticket_type_id');

            return TicketType::whereIn('id', $ids)->get();
        });
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeStripeCheckoutId(Builder $query, string $sessionId): void
    {
        $query->where('stripe_checkout_id', $sessionId);
    }

    public function scopeUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeCurrentUser(Builder $query): void
    {
        $query->where('user_id', Auth::id());
    }

    public function scopeCurrentEvent(Builder $query): void
    {
        $query->where('event_id', Event::getCurrentEventId());
    }

    protected function refundable(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->refunded) {
                    return $this->purchasedTickets->every(function ($ticket, int $key) {
                        /** @var PurchasedTicket $ticket */
                        return $ticket->user_id === $this->user_id;
                    });
                }

                return false;
            },
        );
    }

    public function refund(): void
    {
        if (! $this->refundable) {
            return;
        }

        $stripeService = app(\App\Services\StripeService::class);
        $stripeService->refundOrder($this);

        $this->purchasedTickets->each(function ($ticket) {
            /** @var PurchasedTicket $ticket */
            $ticket->delete();
        });

        $this->refunded = true;
        $this->save();
    }
}
