<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\TicketTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TicketTransferPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'ticketTransfers';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    public function view(User $user, Model $model): bool
    {
        /** @var TicketTransfer $model */
        if ($user->id === $model->user_id ||
            $user->id === $model->recipient_user_id ||
            ($model->completed == false && strcasecmp($user->email, $model->recipient_email) === 0)
        ) {
            return true;
        }

        return parent::view($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var TicketTransfer $model */
        if ($model->completed) {
            return false;
        }

        if ($user->id === $model->user_id) {
            return true;
        }

        return parent::delete($user, $model);
    }

    public function complete(User $user, Model $model): bool
    {
        /** @var TicketTransfer $model */
        if ($model->completed) {
            return false;
        }

        if (strcasecmp($user->email, $model->recipient_email) === 0) {
            return true;
        }

        return false;
    }
}
