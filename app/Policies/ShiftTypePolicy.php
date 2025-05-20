<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\User;
use App\Models\Volunteering\ShiftType;
use App\Policies\AbstractModelPolicy;
use Illuminate\Database\Eloquent\Model;

class ShiftTypePolicy extends AbstractModelPolicy
{
    protected string $prefix = 'shiftTypes';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Volunteers;

    /**
     * Allow users to view all shift types
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * @param  ShiftType  $model
     */
    public function view(User $user, Model $model): bool
    {
        if ($model->team->active && $model->event->active) {
            return true;
        }

        return parent::view($user, $model);
    }
}
