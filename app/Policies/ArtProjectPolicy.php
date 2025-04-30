<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Grants\ArtProject;
use App\Models\User;

class ArtProjectPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'artProjects';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Grants;

    /**
     * @param  ArtProject  $artProject
     */
    public function view(User $user, $artProject): bool
    {
        if ($user?->id === $artProject->user_id) {
            return true;
        }

        if ($user?->can('artProjects.viewPending')) {
            return true;
        }

        return parent::view($user, $artProject);
    }

    /**
     * @param  ArtProject  $artProject
     */
    public function update(User $user, $artProject): bool
    {
        return $user->id === $artProject->user_id || parent::update($user, $artProject);
    }

    /**
     * @param  ArtProject  $artProject
     */
    public function delete(User $user, $artProject): bool
    {
        return $user->id === $artProject->user_id || parent::delete($user, $artProject);
    }
}
