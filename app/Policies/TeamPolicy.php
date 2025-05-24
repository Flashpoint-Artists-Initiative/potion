<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\User;
use App\Models\Volunteering\Team;

class TeamPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'teams';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Volunteers;

    /**
     * Allow unathenticated users to view all events
     *
     * Filtering for non-active teams happens in the TeamsController
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * @param  Team  $team
     */
    public function view(User $user, $team): bool
    {

        if ($team->active && $team->event->active) {
            return true;
        }

        if ($user->can('teams.viewPending')) {
            return true;
        }

        return parent::view($user, $team);
    }
}
