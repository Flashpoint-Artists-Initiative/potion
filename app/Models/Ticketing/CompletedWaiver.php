<?php

declare(strict_types=1);

namespace App\Models\Ticketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as ContractsAuditable;

/**
 * @property-read Waiver $waiver
 * @property-read User $user
 */
class CompletedWaiver extends Model implements ContractsAuditable
{
    use Auditable, HasFactory;

    protected $fillable = [
        'waiver_id',
        'user_id',
        'form_data',
        'paper_completion',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];

    protected $with = [
        'waiver',
    ];

    /**
     * @return BelongsTo<Waiver, $this>
     */
    public function waiver(): BelongsTo
    {
        return $this->belongsTo(Waiver::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
