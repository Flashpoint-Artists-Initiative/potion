<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Observers\CartItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Cart $cart
 * @property-read TicketType $ticketType
 */
#[ObservedBy(CartItemObserver::class)]
class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'ticket_type_id',
        'reserved_ticket_id',
        'quantity',
    ];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class)->withTrashed();
    }
}
