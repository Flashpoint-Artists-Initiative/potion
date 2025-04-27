<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\Waiver;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WaiverPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'waivers';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    /**
     * @param  Waiver  $waiver
     */
    public function update(User $user, Model $waiver): bool
    {
        // Waivers with any completions cannot be modified
        if ($waiver->completedWaivers()->exists()) {
            return false;
        }

        return parent::update($user, $waiver);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Model $waiver): bool
    {
        return true;
    }

    /**
     * @param  Waiver  $waiver
     */
    public function complete(User $user, Model $waiver): bool
    {
        return $user->waivers()->where('waiver_id', $waiver->id)->count() === 0;
    }
}
