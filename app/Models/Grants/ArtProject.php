<?php

declare(strict_types=1);

namespace App\Models\Grants;

use App\Enums\ArtProjectStatusEnum;
use App\Enums\GrantFundingStatusEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read Event $event
 * @property-read User $user
 * @property-read GrantFundingStatusEnum $fundingStatus
 * @property-read float $totalFunding
 * @property-read int $totalVotes
 */
class ArtProject extends Model implements ContractsAuditable, HasMedia
{
    use Auditable, Auditable, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'event_id',
        'artist_name',
        'description',
        'budget_link',
        'min_funding',
        'max_funding',
        'project_status',
    ];

    protected function casts()
    {
        return [
            'project_status' => ArtProjectStatusEnum::class,
            'funding_status' => GrantFundingStatusEnum::class,
            'min_funding' => 'float',
            'max_funding' => 'float',
        ];
    }

    protected $with = [
        'votes',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user_votes')->withTimestamps()->withPivot('votes');
    }

    public function scopeCurrentEvent(Builder $query): Builder
    {
        return $query->where('event_id', Event::getCurrentEventId());
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('project_status', ArtProjectStatusEnum::Approved);
    }

    public function artistName(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                return $value ?: $this->user->display_name;
            },
        );
    }

    public function totalFunding(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->votes->sum('pivot.votes') * $this->event->dollarsPerVote;
            },
        );
    }

    public function totalVotes(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->votes->sum('pivot.votes');
            },
        );
    }

    public function fundingStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $funding = $this->totalFunding;

                return match (true) {
                    $funding >= $this->max_funding => GrantFundingStatusEnum::MaxReached,
                    $funding >= $this->min_funding => GrantFundingStatusEnum::MinReached,
                    default => GrantFundingStatusEnum::Unfunded,
                };
            },
        );
    }

    public function checkVotingStatus(?User $user, bool $throwException = true): bool
    {
        try {
            if (! $this->event->votingEnabled) {
                throw new \Exception('Grant voting is closed for this event');
            }

            if ($this->project_status !== ArtProjectStatusEnum::Approved) {
                throw new \Exception('Only approved projects can be voted on');
            }

            if ($user && $this->votes()->where('user_id', $user->id)->exists()) {
                throw new \Exception('User has already voted for this project');
            }

            if ($this->fundingStatus === GrantFundingStatusEnum::MaxReached) {
                throw new \Exception('Project has already reached maximum funding');
            }
        } catch (\Exception $e) {
            if ($throwException) {
                throw $e;
            }

            return false;
        }

        return true;
    }

    public function vote(User $user, int $numVotes): void
    {
        $this->checkVotingStatus($user);
        $this->votes()->attach($user->id, ['votes' => $numVotes]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->nonQueued()
            ->fit(Fit::Max, 200, 200);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk('s3-art-images')
            ->useFallbackUrl(asset('images/default-art-image.svg'), 'preview');
    }
}
