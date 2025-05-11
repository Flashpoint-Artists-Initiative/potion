<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Models\Concerns\HasTicketType;
use App\Models\Concerns\TicketInterface;
use App\Models\Event;
use App\Observers\ReservedTicketObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property bool $is_purchased
 * @property bool $can_be_purchased
 * @property-read Carbon $final_expiration_date
 */
#[ObservedBy(ReservedTicketObserver::class)]
class ReservedTicket extends Model implements ContractsAuditable, TicketInterface
{
    public ?int $count = 1;

    use Auditable, HasFactory, HasTicketType;

    protected $fillable = [
        'user_id',
        'ticket_type_id',
        'email',
        'expiration_date',
        'note',
    ];

    protected $casts = [
        'expiration_date' => 'datetime',
    ];

    /**
     * @return HasOne<PurchasedTicket, $this>
     */
    public function purchasedTicket(): HasOne
    {
        return $this->hasOne(PurchasedTicket::class)->withTrashed();
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Query scope that matches all of the following:
     * - ticketType.event.active is true
     * - no purchased ticket
     * - Either: There's no expiration_date set on the reservedTicket AND the ticketType is still on sale
     * - Or: There is an expiration_date set on the reservedTicket AND it's not expired
     *
     * @param  Builder<ReservedTicket>  $query
     */
    public function scopeCanBePurchased(Builder $query): void
    {
        $query->whereRelation('ticketType.event', 'active', true);
        $query->whereDoesntHave('purchasedTicket');
        $query->where(function (Builder $query) {
            $query->where(function (Builder $query) {
                $query->whereRelation('ticketType', fn ($query) => $query->onSale());
                $query->where('expiration_date', null);
            });
            $query->orWhere(function (Builder $query) {
                $query->whereNot('expiration_date', null);
                $query->where('expiration_date', '>', now());
            });
        });
        $query->noActiveTransfer();
    }

    public function scopeEvent(Builder $query, int $eventId): void
    {
        $query->whereRelation('ticketType.event', 'id', $eventId);
    }

    public function scopeCurrentEvent(Builder $query): void
    {
        $query->whereRelation('ticketType.event', 'id', Event::getCurrentEventId());
    }

    public function scopeCurrentUser(Builder $query): void
    {
        $query->where('user_id', Auth::id());
    }

    /**
     * @return Attribute<bool, void>
     */
    public function isPurchased(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->purchasedTicket()->exists();
            }
        );
    }

    /**
     * @return Attribute<bool, void>
     */
    public function canBePurchased(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->ticketType->event->active &&
                ! $this->is_purchased &&
                (
                    (! is_null($attributes['expiration_date']) && $attributes['expiration_date'] > now()) ||
                    (is_null($attributes['expiration_date']) && $this->ticketType->onSale)
                );
            }
        );
    }

    public function finalExpirationDate(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return new Carbon($attributes['expiration_date'] ?? $this->ticketType->sale_end_date);
            }
        );
    }
}
