<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ShiftRequirementPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'requirements';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Volunteers;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }
}
