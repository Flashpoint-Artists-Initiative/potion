<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property bool $is_purchased
 */
class ReservedTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'expiration_date',
        'note',
        'name',
    ];

    protected $casts = [
        'expiration_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Check submitted email for a matching user, and if found assign to user_id instead.
        static::saving(function (ReservedTicket $reservedTicket) {
            if ($reservedTicket->isDirty('email')) {
                $user_id = User::where('email', $reservedTicket->email)->value('id');

                if ($user_id) {
                    $reservedTicket->user_id = $user_id;
                    // $reservedTicket->email = null;
                }
            }
        });

        static::updating(function (ReservedTicket $reservedTicket) {
            if ($reservedTicket->is_purchased) {
                return false;
            }
        });

        static::deleting(function (ReservedTicket $reservedTicket) {
            if ($reservedTicket->is_purchased) {
                return false;
            }
        });
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function purchasedTicket(): HasOne
    {
        return $this->hasOne(PurchasedTicket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): HasOneThrough
    {
        // Set the keys directly because we're effectively going backwards from the indended way
        return $this->hasOneThrough(
            Event::class,
            TicketType::class,
            'id', // Foreign Key for ticketType
            'id', // Foreign Key for Event
            'ticket_type_id', // Local key for purchasedTicket
            'event_id' //Local key for ticketType
        );
    }

    public function isPurchased(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->purchasedTicket()->exists();
            }
        );
    }
}
