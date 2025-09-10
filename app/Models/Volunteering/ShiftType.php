<?php

declare(strict_types=1);

namespace App\Models\Volunteering;

use App\Models\Event;
use App\Models\User;
use Filament\Support\Colors\Color;
use Guava\Calendar\Contracts\Resourceable;
use Guava\Calendar\ValueObjects\CalendarResource;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;
use Spatie\Color\Rgb;

/**
 * @property int $total_num_spots
 * @property float $percent_filled
 * @property-read Team $team
 * @property-read Event $event
 */
class ShiftType extends Model implements ContractsAuditable, Resourceable
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'location',
        'length',
        'num_spots',
        'details', // JSON field for predefined details
    ];

    protected $withCount = [
        'volunteers',
    ];

    protected $with = [
        'requirements',
    ];

    protected $casts = [
        'details' => AsArrayObject::class,
    ];

    // This is the easiest way to set a default value for a JSON column
    // Don't add default key => values here, instead always check if they exist
    // That way there's never an issue of backwards incompatibility when adding new settings
    protected $attributes = [
        'details' => '{}',
    ];

    /**
     * @return HasOneThrough<Event, Team, $this>
     */
    public function event(): HasOneThrough
    {
        // Set the keys directly because we're effectively going backwards from the intended way
        return $this->hasOneThrough(
            Event::class,
            Team::class,
            'id', // Foreign Key for team
            'id', // Foreign Key for event
            'team_id', // Local key for shiftType
            'event_id' // Local key for team
        );
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * @return BelongsToMany<Requirement, $this>
     */
    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(Requirement::class, 'shift_type_requirements')->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'volunteer_data');
    }

    /**
     * @return Attribute<int,never>
     */
    protected function totalNumSpots(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->shifts->sum('num_spots');
            }
        );
    }

    /**
     * @return Attribute<float,never>
     */
    protected function percentFilled(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $total = 100 * ($this->volunteers_count / max(1, $this->total_num_spots));

                return sprintf('%.1f', $total);
            }
        );
    }

    public function toCalendarResource(): CalendarResource
    {
        $colors = array_values(array_slice(Color::all(), 5));
        $shiftTypes = $this->team->shiftTypes;
        $index = $shiftTypes->search(fn ($item) => $item->id === $this->id);
        // $offset = (360 / $shiftTypes->count()) * $index;
        $offset = ((int) (count($colors) / $shiftTypes->count())) * $index % count($colors);

        return CalendarResource::make($this)
            ->title($this->title)
            ->eventBackgroundColor((string) Rgb::fromString('rgb(' . $colors[$offset]['700'] . ')')->toHex());
        // ->eventBackgroundColor($this->hsv2rgb($offset, 100, 50));
    }

    /**
     * A simple way to access nested settings attributes
     *
     * @param  string  $key  The key to access in the settings array, in dot notation
     * @param  mixed  $default  The default value to return if the key does not exist.  If null, it will check the config file for a default value.
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        return Arr::dot($this->details ?? [])[$key] ?? $default;
    }

    /**
     * @return Attribute<bool|string|null,never>
     */
    protected function shadeProvided(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('shade_provided.state'));
    }

    /**
     * @return Attribute<string,never>
     */
    protected function shadeProvidedNote(): Attribute
    {
        return Attribute::get(fn () => (string) $this->getSetting('shade_provided.note', ''));
    }

    /**
     * @return Attribute<bool|string|null,never>
     */
    protected function longStanding(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('long_standing.state'));
    }

    /**
     * @return Attribute<string,never>
     */
    protected function longStandingNote(): Attribute
    {
        return Attribute::get(fn () => (string) $this->getSetting('long_standing.note', ''));
    }

    /**
     * @return Attribute<string,never>
     */
    protected function physicalRequirementsNote(): Attribute
    {
        return Attribute::get(fn () => (string) $this->getSetting('physical_requirements.note', ''));
    }
}
