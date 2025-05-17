<?php

declare(strict_types=1);

namespace App\Models\Volunteering;

use App\Models\User;
use Carbon\Carbon;
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
 * @property int $end_offset
 * @property string $title
 * @property string $start_datetime
 * @property string $end_datetime
 * @property int $volunteers_count
 * @property float $percentFilled
 * @property int $num_spots
 * @property-read ShiftType $shiftType
 * @property-read Team $team
 */
class Shift extends Model implements ContractsAuditable
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
    public function length(): Attribute
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
    public function numSpots(): Attribute
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
     * @return Attribute<int, void>
     */
    public function endOffset(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_offset + $this->length
        );
    }

    /**
     * @return Attribute<string, void>
     */
    public function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shiftType->title
        );
    }

    /**
     * @return Attribute<string, void>
     */
    public function startDatetime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $eventStart = $this->team->event->start_date;
                $start = new Carbon($eventStart);
                $start->addMinutes($this->start_offset);

                return $start->format('Y-m-d H:i:s');
            }
        );
    }

    /**
     * @return Attribute<string, void>
     */
    public function endDatetime(): Attribute
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
    public function percentFilled(): Attribute
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
}
