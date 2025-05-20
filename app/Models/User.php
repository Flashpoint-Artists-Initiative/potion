<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\EmailUpdated;
use App\Models\Concerns\HasVirtualColumns;
use App\Models\Grants\ArtProject;
use App\Models\Ticketing\Cart;
use App\Models\Ticketing\CompletedWaiver;
use App\Models\Ticketing\Order;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketTransfer;
use App\Models\Volunteering\Shift;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mchev\Banhammer\Traits\Bannable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read string $display_name
 * @property-read string $name
 */
class User extends Authenticatable implements ContractsAuditable, FilamentUser, HasName, JWTSubject, MustVerifyEmail
{
    use Auditable, Bannable, HasFactory, HasRoles, HasVirtualColumns, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'legal_name',
        'preferred_name',
        'email',
        'password',
        'birthday',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var string[] */
    protected $auditExclude = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birthday' => 'date:Y/m/d',
    ];

    /**
     * The virtual generated columns on the model
     *
     * @var array<int, string>
     */
    protected $virtualColumns = [
        'display_name',
    ];

    protected static function booted(): void
    {
        // Send a new verification email when the email address changes
        static::updating(function (User $user) {
            if ($user->isDirty('email') && $user->hasVerifiedEmail()) {
                $user->email_verified_at = null;
                event(new EmailUpdated($user));
            }
        });
    }

    /**
     * @return HasMany<PurchasedTicket, $this>
     */
    public function purchasedTickets(): HasMany
    {
        return $this->hasMany(PurchasedTicket::class);
    }

    /**
     * @return HasMany<ReservedTicket, $this>
     */
    public function reservedTickets(): HasMany
    {
        return $this->hasMany(ReservedTicket::class);
    }

    /**
     * @return HasMany<ReservedTicket, $this>
     */
    public function availableReservedTickets(): HasMany
    {
        return $this->hasMany(ReservedTicket::class)
            ->where(fn ($query) => $query->canBePurchased()->noActiveTransfer());
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Cart, $this>
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * @return HasOne<Cart, $this>
     */
    public function activeCart(): HasOne
    {
        return $this->hasOne(Cart::class)
            ->where(fn ($query) => $query->notExpired());
    }

    /**
     * @return HasMany<CompletedWaiver, $this>
     */
    public function waivers(): HasMany
    {
        return $this->hasMany(CompletedWaiver::class);
    }

    /**
     * @return HasMany<TicketTransfer, $this>
     */
    public function ticketTransfers(): HasMany
    {
        return $this->hasMany(TicketTransfer::class);
    }

    /**
     * @return HasMany<TicketTransfer, $this>
     */
    public function receivedTicketTransfers(): HasMany
    {
        return $this->hasMany(TicketTransfer::class, 'recipient_email', 'email')
            ->orWhere('recipient_user_id', $this->id);
    }

    /**
     * @return BelongsToMany<Shift, $this, Pivot, 'signup'>
     */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shift_signups')->as('signup')->withTimestamps();
    }

    public function votes(): BelongsToMany
    {
        return $this->belongsToMany(ArtProject::class, 'project_user_votes')->withTimestamps()->withPivot('votes');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): int
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return mixed[]
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Ensures the same permissions and roles are used for both API and web requests
     */
    protected function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * What gets shown in the top right of the Filament panel
     */
    public function getFilamentName(): string
    {
        return $this->display_name;
    }

    public function getFilamentBanhammerTitleAttribute(): string
    {
        return $this->display_name;
    }

    /**
     * Auth for Filament Panels
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $id = $panel->getId();
        if ($id == 'app') {
            return true;
        }

        return $this->can("panelAccess.{$id}");
    }

    public function hasSignedWaiverForEvent(int $eventId): bool
    {
        return $this->waivers()->whereHas('waiver', function ($query) use ($eventId) {
            return $query->where('event_id', $eventId);
        })->count() > 0;
    }

    public function hasVotedArtProjectsForEvent(int $eventId): bool
    {
        return $this->votes()->where('event_id', $eventId)->exists();
    }

    /**
     * @return Collection<int,PurchasedTicket>
     */
    public function getValidTicketsForEvent(?int $eventId = null): Collection
    {
        $tickets = $this->purchasedTickets()->noActiveTransfer()->whereRelation('ticketType', fn ($query) => $query->admittance($eventId))->with('ticketType')->get();

        return $tickets;
    }

    /**
     * @return Attribute<string,never>
     */
    protected function birthday(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['birthday'],
            set: fn (string $value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    /**
     * Used for Audits
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['display_name'],
        );
    }
}
