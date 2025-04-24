<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\RolesEnum;
use App\Events\ArtProjectFundingStatusChange;
use App\Models\User;
use App\Notifications\ArtProjectFundingStatusNotification;

class ArtProjectFundingStatusListener
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
    public function handle(ArtProjectFundingStatusChange $event): void
    {

        // Get list of users to notify.  All art-grant users, and the project owner
        $users = User::role(RolesEnum::ArtGrantReviewer)->get();

        if ($event->project->user) {
            $users->push($event->project->user);
        }

        $users->each(function (User $user) use ($event) {
            $user->notify(new ArtProjectFundingStatusNotification(
                $event->project,
                $user->id === $event->project->user_id
            ));
        });
    }
}
