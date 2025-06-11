<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CartStatusEnum;
use App\Enums\LockdownEnum;
use App\Models\Ticketing\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CartPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'carts';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function complete(User $user, Model $model): bool
    {
        /** @var Cart $model */
        return $user->can('carts.complete') && $model->status === CartStatusEnum::Expired;
    }
}
