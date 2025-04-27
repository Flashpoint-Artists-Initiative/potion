<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\ReservedTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReservedTicketPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'reservedTickets';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    /**
     * @param  ReservedTicket  $model
     */
    public function update(User $user, Model $model): bool
    {
        if ($model->is_purchased) {
            return false;
        }

        return parent::update($user, $model);
    }

    /**
     * @param  ReservedTicket  $model
     */
    public function delete(User $user, Model $model): bool
    {
        if ($model->is_purchased) {
            return false;
        }

        return parent::delete($user, $model);
    }
}
