<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Ticketing\Order;
use Illuminate\Database\Eloquent\Model;

class OrderPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'orders';

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
