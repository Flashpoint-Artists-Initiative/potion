<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Models\Concerns\HasTicketType;
use App\Models\Concerns\TicketInterface;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property-read ?ReservedTicket $reservedTicket
 */
class PurchasedTicket extends Model implements ContractsAuditable, TicketInterface
{
    use Auditable, HasFactory, HasTicketType, SoftDeletes;

    protected $fillable = [
        'user_id',
        'ticket_type_id',
        'reserved_ticket_id',
        'order_id',
    ];

    /**
     * @return BelongsTo<ReservedTicket, $this>
     */
    public function reservedTicket(): BelongsTo
    {
        return $this->belongsTo(ReservedTicket::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeCurrentEvent(Builder $query): void
    {
        $query->whereRelation('ticketType.event', 'id', Event::getCurrentEventId());
    }

    public function scopeCurrentUser(Builder $query): void
    {
        $query->where('user_id', Auth::id());
    }

    public function scopeTransferable(Builder $query): void
    {
        $query->whereRelation('ticketType', 'transferable', true);
    }

    public static function createFromCartItem(CartItem $item, ?int $userId = null, ?int $orderId = null): void
    {
        for ($i = 0; $i < $item->quantity; $i++) {
            static::create([
                'user_id' => $userId ?? $item->cart->user_id,
                'ticket_type_id' => $item->ticket_type_id,
                'reserved_ticket_id' => $item->reserved_ticket_id,
                'order_id' => $orderId,
            ]);
        }
    }
}
