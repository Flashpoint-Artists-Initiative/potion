<?php

declare(strict_types=1);

namespace App\Models\Volunteering;

use App\Models\User;
use App\Observers\ShiftObserver;
use Carbon\Carbon;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property-read string $title
 * @property-read Team $team
 * @property-read ShiftType $shiftType
 * @property-read Carbon $startCarbon
 * @property-read Carbon $startDatetime
 * @property-read Carbon $endDatetime
 */
#[ObservedBy(ShiftObserver::class)]
class Shift extends Model implements ContractsAuditable, Eventable
{
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Used to determine if volunteers should be notified of a shift change or deletion.
     */
    public bool $notifyVolunteersOnChange = true;

    /**
     * The reason for the change, if applicable.
     * This is used when notifying volunteers of the change.
     */
    public ?string $changeReason = null;

    protected $fillable = [
        'shift_type_id',
        'start_offset',
        'length',
        'length_in_hours',
        'num_spots',
        'multiplier',
        'changeReason',
        'start_datetime',
    ];

    protected $with = [
        'shiftType',
        'team',
    ];

    protected $withCount = [
        'volunteers',
    ];

    protected $appends = [
        'length_in_hours',
        'end_offset',
        'start_datetime',
    ];

    public function update(array $attributes = [], array $options = [])
    {
        // Check if `changeReason` is in the attributes
        if (array_key_exists('changeReason', $attributes)) {
            $this->changeReason = $attributes['changeReason'];
            unset($attributes['changeReason']); // Remove it from the attributes to avoid database interaction
        }

        return parent::update($attributes, $options);
    }

    /**
     * @return BelongsTo<ShiftType, $this>
     */
    public function shiftType(): BelongsTo
    {
        return $this->belongsTo(ShiftType::class);
    }

    /**
     * @return BelongsToMany<User, $this, Pivot, 'signup'>
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_signups')->as('signup')->withTimestamps();
    }

    // public function requirements(): BelongsToMany
    // {
    //     return $this->belongsToMany(Requirement::class, 'shift_requirements')->withTimestamps();
    // }

    /**
     * @return HasOneThrough<Team, ShiftType, $this>
     */
    public function team(): HasOneThrough
    {
        // Set the keys directly because we're effectively going backwards from the intended way
        return $this->hasOneThrough(
            Team::class,
            ShiftType::class,
            'id', // Foreign Key for shiftType
            'id', // Foreign Key for team
            'shift_type_id', // Local key for shift
            'team_id' // Local key for shiftType
        );
    }

    /**
     * @return Attribute<float,array{length:int}>
     */
    protected function lengthInHours(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['length'] / 60,
            set: fn (mixed $value, array $attributes) => ['length' => $value * 60]
        );
    }

    /**
     * @return Attribute<int,never>
     */
    protected function endOffset(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_offset + $this->length
        );
    }

    /**
     * @return Attribute<string,never>
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shiftType->title
        );
    }

    /**
     * @return Attribute<string,array{start_offset:int}>
     */
    protected function startDatetime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $baseDate = $this->team->event->volunteerBaseDate->copy();
                $baseDate->addMinutes($this->start_offset);

                return $baseDate->format('Y-m-d H:i:s');
            },
            set: function (string $value) {
                $value = Carbon::parse($value, 'America/New_York');
                $baseDate = $this->team->event->volunteerBaseDate;
                return ['start_offset' => $baseDate->diffInMinutes($value)];
            }
        );
    }

    /**
     * @return Attribute<Carbon,never>
     */
    protected function startCarbon(): Attribute
    {
        return Attribute::get(function () {
            $baseDate = $this->team->event->volunteerBaseDate->copy();
            $baseDate->addMinutes($this->start_offset);

            return $baseDate;
        });
    }

    /**
     * @return Attribute<non-falsy-string,never>
     */
    protected function endDatetime(): Attribute
    {
        return Attribute::get(function () {
            $baseDate = $this->team->event->volunteerBaseDate->copy();
            $baseDate->addMinutes($this->end_offset);

            return $baseDate->format('Y-m-d H:i:s');
        });
    }

    /**
     * @return Attribute<float,never>
     */
    protected function percentFilled(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $total = 100 * ($this->volunteers_count / max(1, $this->num_spots));

                return sprintf('%.1f', $total);
            }
        );
    }

    // protected function changeReason(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => $this->changeReason,
    //         set: fn (?string $value) => $this->changeReason = $value,
    //     );
    // }

    public function overlapsWith(Shift $shift): bool
    {
        return max($this->start_offset, $shift->start_offset) < min($this->end_offset, $shift->end_offset);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        return CalendarEvent::make($this)
            ->title($this->getCalendarEventTitle())
            ->start($this->startDatetime)
            ->end($this->endDatetime)
            ->resourceId($this->shiftType->id)
            ->action('edit');
    }

    protected function getCalendarEventTitle(): string
    {
        $spotsPlural = str('spots')->plural($this->num_spots ?? 0);
        $signupsPlural = str('signup')->plural($this->volunteers_count);

        return sprintf("%s\n%s %s\n%s %s(%.1f%%)",
            $this->title,
            $this->num_spots,
            $spotsPlural,
            $this->volunteers_count,
            $signupsPlural,
            $this->percentFilled
        );
    }

    /**
     * Used to determine if volunteers should be notified of a shift change or deletion.
     *
     * Use this instead of updateQuietly() or deleteQuietly() to ensure that we still receive other events for auditing.
     */
    public function dontNotifyVolunteers(): static
    {
        $this->notifyVolunteersOnChange = false;

        return $this;
    }
}
