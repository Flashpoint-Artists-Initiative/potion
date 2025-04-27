<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'orders';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function refund(User $user, Model $model): bool
    {
        /** @var Order $model */
        return $user->can('orders.refund') && $model->refundable;
    }
}
