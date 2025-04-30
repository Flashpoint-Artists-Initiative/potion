<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'users';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    /**
     * @param  User  $model
     */
    public function update(User $user, Model $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return parent::update($user, $model);
    }

    /**
     * @param  User  $model
     */
    public function view(User $user, Model $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return parent::view($user, $model);
    }

    /**
     * @param  User  $model
     */
    public function delete(User $user, Model $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return parent::delete($user, $model);
    }
}
