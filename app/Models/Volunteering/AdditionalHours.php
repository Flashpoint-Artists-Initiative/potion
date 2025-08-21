<?php

declare(strict_types=1);

namespace App\Models\Volunteering;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalHours extends Model
{
    protected $table = 'volunteer_hours';

    protected $fillable = [
        'team_id',
        'user_id',
        'hours',
        'note',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
