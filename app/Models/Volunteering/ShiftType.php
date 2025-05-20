<?php

declare(strict_types=1);

namespace App\Models\Volunteering;

use App\Models\Event;
use App\Models\User;
use Filament\Support\Colors\Color;
use Guava\Calendar\Contracts\Resourceable;
use Guava\Calendar\ValueObjects\CalendarResource;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    ];

    protected $withCount = [
        'volunteers',
    ];

    protected $with = [
        'requirements',
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
}
