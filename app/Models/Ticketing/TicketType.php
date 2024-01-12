<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $remaining_ticket_count
 * @property ?string $cart_items_quantity
 * @property bool $available
 */
class TicketType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'sale_start_date',
        'sale_end_date',
        'quantity',
        'price',
        'active',
    ];

    protected $casts = [
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'active' => 'boolean',
    ];

    protected $withCount = [
        'purchasedTickets',
        'unsoldReservedTickets',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function purchasedTickets(): HasMany
    {
        return $this->hasMany(PurchasedTicket::class);
    }

    public function reservedTickets(): HasMany
    {
        return $this->hasMany(ReservedTicket::class);
    }

    public function unsoldReservedTickets(): HasMany
    {
        return $this->hasMany(ReservedTicket::class)
            ->where('expiration_date', '>', now())
            ->whereDoesntHave('purchasedTicket');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function activeCartItems(): HasMany
    {
        return $this->hasMany(CartItem::class)
            ->whereHas('cart', fn ($query) => $query->where('expiration_date', '>', now()));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', 1);
    }

    public function scopeOnSale(Builder $query): void
    {
        $query->where('sale_start_date', '<=', now());
        $query->where('sale_end_date', '>=', now());
    }

    public function scopeSaleStarted(Builder $query): void
    {
        $query->where('sale_start_date', '<=', now());
    }

    public function scopeHasQuantity(Builder $query): void
    {
        $query->where('quantity', '>', 0);
    }

    public function scopeEvent(Builder $query, int $eventId): void
    {
        $query->where('event_id', $eventId);
    }

    public function scopeAvailable(Builder $query): void
    {
        // @phpstan-ignore-next-line
        $query->active()->onSale()->hasQuantity();
    }

    /**
     * Overloaded method to eager load a sum aggregate
     */
    public function newQueryWithoutScopes()
    {
        $query = parent::newQueryWithoutScopes();

        return $query->withSum('activeCartItems as cart_items_quantity', 'quantity');
    }

    public function remainingTicketCount(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($attributes['quantity'] == 0) {
                    return 0;
                }

                return $attributes['quantity']
                     - $attributes['unsold_reserved_tickets_count']
                     - $attributes['purchased_tickets_count']
                     - $attributes['cart_items_quantity'];
            }
        );
    }

    public function available(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->remaining_ticket_count > 0
                && $attributes['active'] == true
                && now() < $attributes['sale_end_date']
                && now() > $attributes['sale_start_date'];
            }
        );
    }

    public function hasAvailable(int $quantity): bool
    {
        return $this->available && $this->remaining_ticket_count > $quantity;
    }
}
