<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\TicketTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TicketTransferAdminPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'ticketTransfers';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    public function delete(User $user, Model $model): bool
    {
        /** @var TicketTransfer $model */
        if ($model->completed) {
            return false;
        }

        return parent::delete($user, $model);
    }
}
