<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

// TODO: This was setup the way it was when it was API only.  For Filament it doesn't appear
// that it should have any custom logic.  Revisit later.
class TicketTypePolicy extends AbstractModelPolicy
{
    protected string $prefix = 'ticketTypes';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;

    /**
     * Allow unathenticated users to view all ticket types
     *
     * Filtering for non-active types happens in the TicketTypesController
     */
    // public function viewAny(?User $user): bool
    // {
    //     return true;
    // }

    /**
     * @param  TicketType  $ticketType
     */
    // public function view(?User $user, Model $ticketType): bool
    // {
    //     if ($ticketType->active) {
    //         return true;
    //     }

    //     if ($user?->can('ticketTypes.viewPending')) {
    //         return true;
    //     }

    //     return false;
    // }
}
