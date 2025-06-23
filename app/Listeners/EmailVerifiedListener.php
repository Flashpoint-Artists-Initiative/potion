<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Ticketing\ReservedTicket;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class EmailVerifiedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        /** @var User $user */
        $user = $event->user;

        // Set appropriate reserved tickets to the user.
        // Free purchased tickets are created by the ReservedTicketObserver
        ReservedTicket::where('email', $user->email)->where('user_id', null)->each(
            function (ReservedTicket $reservedTicket) use ($user) {
                $reservedTicket->user_id = $user->id;
                $reservedTicket->save();
            }
        );
    }
}
