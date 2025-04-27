<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PurchasedTicketPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'purchasedTickets';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    /**
     * Allow unathenticated users to view all of their own purchased tickets
     *
     * Filtering happens in the PurchasedTicketsController
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * @param  PurchasedTicket  $purchasedTicket
     */
    public function view(User $user, Model $purchasedTicket): bool
    {
        if ($user->id === $purchasedTicket->user_id) {
            return true;
        }

        return parent::view($user, $purchasedTicket);
    }
}
