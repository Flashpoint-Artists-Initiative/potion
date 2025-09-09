<?php

declare(strict_types=1);

namespace App\Models\Volunteering;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;
use Illuminate\Support\Str;

/**
 * @property int $total_num_spots
 * @property float $percent_filled
 * @property-read Event $event
 */
class Team extends Model implements ContractsAuditable
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'email',
        'active',
    ];

    protected $withCount = [
        'volunteers',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<ShiftType, $this>
     */
    public function shiftTypes(): HasMany
    {
        return $this->hasMany(ShiftType::class);
    }

    /**
     * @return HasManyThrough<Shift, ShiftType, $this>
     */
    public function shifts(): HasManyThrough
    {
        return $this->hasManyThrough(Shift::class, ShiftType::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'volunteer_data');
    }

    /**
     * @return HasMany<AdditionalHours, $this>
     */
    public function additionalHours(): HasMany
    {
        return $this->hasMany(AdditionalHours::class, 'team_id');
    }

    public function scopeCurrentEvent(Builder $query): Builder
    {
        return $query->where('event_id', Event::getCurrentEventId());
    }

    /**
     * @return Attribute<string,never>
     */
    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name
                ? Str::slug($this->name)
                : null
        );
    }

    /**
     * @return Attribute<int,never>
     */
    protected function totalNumSpots(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->shifts->sum(function (Shift $shift) {
                    if ($shift->multiplier == 0.0) {
                        return 0;
                    }

                    return $shift->num_spots;
                });
            }
        );
    }
    
    /**
     * @return Attribute<int,never>
     */
    protected function totalFilledSpots(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->shifts->sum(function (Shift $shift) {
                    if ($shift->multiplier == 0.0) {
                        return 0;
                    }

                    return $shift->volunteers_count;
                });
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
                $total = 100 * ($this->totalFilledSpots / max(1, $this->total_num_spots));

                return sprintf('%.1f', $total);
            }
        );
    }

    /**
     * @param  Builder<Team>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', 1);
    }

    /**
     * @param  Builder<Team>  $query
     */
    public function scopeEvent(Builder $query, int $eventId): void
    {
        $query->where('event_id', $eventId);
    }
}
