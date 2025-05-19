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
 * @property string $title
 * @property string $startDatetime
 * @property string $endDatetime
 * @property float $length_in_hours
 * @property int $volunteers_count
 * @property int $end_offset
 * @property float $percentFilled
 * @property-read ShiftType $shiftType
 * @property-read Team $team
 */
// #[ObservedBy(ShiftObserver::class)]
class Shift extends Model implements ContractsAuditable, Eventable
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_type_id',
        'start_offset',
        'length',
        'num_spots',
        'multiplier',
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
    ];

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
     * Pulls default value from shiftType if not set
     *
     * @return Attribute<int, void>
     */
    protected function length(): Attribute
    {
        return Attribute::make(
            get: function (?int $length) {
                return $length ?? $this->shiftType->length;
            }
        );
    }

    /**
     * Pulls default value from shiftType if not set
     *
     * @return Attribute<int, void>
     */
    protected function numSpots(): Attribute
    {
        return Attribute::make(
            get: function (?int $numSpots) {
                // Null-safe accessor for when a shiftType is deleted,
                // it returns the model including sum(num_spots) for the child shifts
                // TODO: Add events to delete child shifts, solving this problem
                /** @phpstan-ignore-next-line */
                return $numSpots ?? $this->shiftType?->num_spots;
            }
        );
    }

    /**
     * Pulls default value from shiftType if not set
     *
     * @return Attribute<float, void>
     */
    protected function lengthInHours(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $length = $attributes['length'] ?? $this->shiftType->length;

                return $length / 60;
            },
            set: fn (mixed $value, array $attributes) => [
                'length' => $value * 60,
            ]
        );
    }

    /**
     * @return Attribute<int, void>
     */
    protected function endOffset(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_offset + $this->length
        );
    }

    /**
     * @return Attribute<string, void>
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shiftType->title
        );
    }

    /**
     * @return Attribute<string, void>
     */
    protected function startDatetime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $eventStart = $this->team->event->start_date;
                $start = new Carbon($eventStart);
                $start->addMinutes($this->start_offset);

                return $start->format('Y-m-d H:i:s');
            },
            set: function (string $value) {
                $eventStart = $this->team->event->start_date;
                $start = new Carbon($eventStart);
                $value = Carbon::parse($value);
                $start->diffInMinutes($value);

                return $start->format('Y-m-d H:i:s');
            }
        );
    }

    /**
     * @return Attribute<string, void>
     */
    protected function endDatetime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $eventStart = $this->team->event->start_date;
                $start = new Carbon($eventStart);
                $start->addMinutes($this->end_offset);

                return $start->format('Y-m-d H:i:s');
            }
        );
    }

    /**
     * @return Attribute<float, void>
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
        return $this->title . "\n" .
            $this->num_spots . " spots\n" .
            $this->volunteers_count . ' signed up';
    }
}
