<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageContentEnum;
use App\Models\Concerns\HasSettingsAttributes;
use App\Models\Ticketing\CompletedWaiver;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\Ticketing\Waiver;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property ArrayObject $settings
 * @property-read Carbon $volunteerBaseDate
 */
class Event extends Model implements ContractsAuditable
{
    use Auditable, HasFactory, HasSettingsAttributes, SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'start_date',
        'end_date',
        'active',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date:Y/m/d',
        'end_date' => 'date:Y/m/d',
        'active' => 'boolean',
        'settings' => AsArrayObject::class,
    ];

    // This is the easiest way to set a default value for a JSON column
    // Don't add default key => values here, instead always check if they exist
    // That way there's never an issue of backwards incompatibility when adding new settings
    protected $attributes = [
        'settings' => '{}',
    ];

    /**
     * @return HasMany<TicketType, $this>
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * @return HasManyThrough<PurchasedTicket, TicketType, $this>
     */
    public function purchasedTickets(): HasManyThrough
    {
        return $this->hasManyThrough(PurchasedTicket::class, TicketType::class);
    }

    /**
     * @return HasManyThrough<ReservedTicket, TicketType, $this>
     */
    public function reservedTickets(): HasManyThrough
    {
        return $this->hasManyThrough(ReservedTicket::class, TicketType::class);
    }

    /**
     * @return HasMany<Waiver, $this>
     */
    public function waivers(): HasMany
    {
        return $this->hasMany(Waiver::class);
    }

    /**
     * @return HasOne<Waiver, $this>
     */
    public function waiver(): HasOne
    {
        return $this->hasOne(Waiver::class);
    }

    /**
     * @return HasManyThrough<CompletedWaiver, Waiver, $this>
     */
    public function completedWaivers(): HasManyThrough
    {
        return $this->hasManyThrough(CompletedWaiver::class, Waiver::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasManyThrough<ShiftType, Team, $this>
     */
    public function shiftTypes(): HasManyThrough
    {
        return $this->hasManyThrough(ShiftType::class, Team::class);
    }

    /**
     * @return HasOne<PageContent, $this>
     */
    public function appDashboardContent(): HasOne
    {
        return $this->hasOne(PageContent::class)->where('page', PageContentEnum::AppDashboard);
    }

    /**
     * @return HasOne<PageContent, $this>
     */
    public function ticketPurchaseContent(): HasOne
    {
        return $this->hasOne(PageContent::class)->where('page', PageContentEnum::TicketPurchase);
    }

    /**
     * @return HasOne<PageContent, $this>
     */
    public function checkoutContent(): HasOne
    {
        return $this->hasOne(PageContent::class)->where('page', PageContentEnum::Checkout);
    }

    /**
     * @return HasOne<PageContent, $this>
     */
    public function checkoutCompleteContent(): HasOne
    {
        return $this->hasOne(PageContent::class)->where('page', PageContentEnum::CheckoutComplete);
    }

    /**
     * @return HasOne<PageContent, $this>
     */
    public function artGrantVotingContent(): HasOne
    {
        return $this->hasOne(PageContent::class)->where('page', PageContentEnum::ArtGrantVoting);
    }

    /**
     * @return Attribute<string,never>
     */
    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['start_date'],
            set: fn (string $value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    /**
     * @return Attribute<string,never>
     */
    protected function endDate(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['end_date'],
            set: fn (string $value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    /**
     * @return Attribute<Carbon,never>
     */
    protected function startDateCarbon(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Carbon::parse($attributes['start_date']),
        );
    }

    /**
     * @return Attribute<Carbon,never>
     */
    protected function endDateCarbon(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Carbon::parse($attributes['end_date']),
        );
    }

    /**
     * @return Attribute<bool,never>
     */
    protected function votingIsOpen(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->getSetting('art.voting_enabled')
                    && now()->lessThan($this->getSetting('art.voting_end_date', now()->addMinute()));
            }
        );
    }

    /**
     * @return Attribute<?Carbon,never>
     */
    protected function nextTicketSaleDate(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->ticketTypes()
                ->active()
                ->hasQuantity()
                ->where('sale_start_date', '>=', now())
                ->orderBy('sale_start_date')
                ->first()
                ?->sale_start_date,
        );
    }

    /**
     * @return Attribute<?Carbon,never>
     */
    protected function finalTicketEndDate(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->ticketTypes()
                ->active()
                ->hasQuantity()
                ->orderBy('sale_end_date', 'desc')
                ->first()
                ?->sale_end_date,
        );
    }

    protected function volunteerSignupsOpen(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => now()->greaterThan($this->getSetting('volunteering.signups_start', now()->addMinute()))
                && now()->lessThan($this->getSetting('volunteering.signups_end', now()->subMinute()))
        );
    }

    /**
     * Returns the current event ID from the session,
     * otherwise the default set in the config, otherwise 0
     */
    public static function getCurrentEventId(): int
    {
        return session('active_event_id', (int) config('app.default_event_id'));
    }

    public static function getCurrentEvent(): ?Event
    {
        return once(fn () => Event::find(Event::getCurrentEventId()));
    }
}
