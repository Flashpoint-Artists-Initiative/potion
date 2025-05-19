<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Enums\CartStatusEnum;
use App\Models\Event;
use App\Models\User;
use App\Observers\CartObserver;
use App\Services\StripeService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property bool $isExpired
 * @property Event $event
 * @property int $quantity
 * @property-read User $user
 * @property string $stripe_checkout_id
 * @property-read int $subtotal
 * @property-read int $taxesOwed
 * @property-read int $feesOwed
 * @property-read int $total
 * @property-read CartStatusEnum $status
 *
 * @method Builder<static> notExpired()
 */
#[ObservedBy(CartObserver::class)]
class Cart extends Model implements ContractsAuditable
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
    ];

    protected $casts = [
        'expiration_date' => 'datetime',
    ];

    protected $with = [
        'items.ticketType',
    ];

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<Order, $this>
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /**
     * @return Attribute<Event, void>
     */
    protected function event(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->items->firstOrFail()->ticketType->event;
            }
        );
    }

    /**
     * @return Attribute<int, void>
     */
    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->items->sum('quantity');
            }
        );
    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->items->sum(fn (CartItem $item) => $item->ticketType->price * $item->quantity * 100); // in cents
            }
        );
    }

    protected function taxesOwed(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return App::make(StripeService::class)->calculateTaxesAndFees($this->subtotal)['tax'];
            }
        );
    }

    protected function feesOwed(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return App::make(StripeService::class)->calculateTaxesAndFees($this->subtotal)['fees'];
            }
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->subtotal + $this->taxesOwed + $this->feesOwed;
            }
        );
    }

    /**
     * @return Attribute<bool, void>
     */
    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $attributes['expiration_date'] < now();
            }
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($this->order()->exists()) {
                    return CartStatusEnum::Completed;
                }

                if ($this->isExpired) {
                    return CartStatusEnum::Expired;
                }

                return CartStatusEnum::Active;
            }
        );
    }

    public function expire(): void
    {
        // is_expired doesn't trigger correctly if it's been less than a second, so subtract 2 to be sure
        $this->expiration_date = now()->subSeconds(2);
        $this->saveQuietly();
    }

    /**
     * @param  Builder<Cart>  $query
     */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where('expiration_date', '>', now());
    }

    /**
     * @param  Builder<Cart>  $query
     */
    public function scopeStripeCheckoutId(Builder $query, string $id): void
    {
        $query->where('stripe_checkout_id', $id);
    }

    public function setStripeCheckoutIdAndSave(string $id): void
    {
        $this->stripe_checkout_id = $id;
        $this->saveQuietly();
    }
}
