<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\ReservedTicketCreatedMail;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ReservedTicketObserver
{
    public function creating(ReservedTicket $reservedTicket): void
    {
        // Check submitted email for a matching user, and if found assign to user_id
        if ($reservedTicket->isDirty('email')) {
            $user_id = User::where('email', $reservedTicket->email)->value('id');

            if ($user_id) {
                $reservedTicket->user_id = $user_id;
            }
        }

        // When importing reserved tickets, count is optionally set
        // Create duplicate reserved tickets based on $count
        $count = $reservedTicket->count ?? 1;
        for ($i = 1; $i < $count; $i++) {
            $duplicate = $reservedTicket->replicate();
            $duplicate->saveQuietly(); // We don't want to trigger the observer again. The original model will trigger the email
        }
    }

    public function saved(ReservedTicket $reservedTicket): void
    {
        // If the reserved ticket type has a price of 0, automatically create a purchased ticket when possible
        if ($reservedTicket->user_id &&
        $reservedTicket->ticketType->price === 0 &&
        $reservedTicket->can_be_purchased
        ) {
            $purchasedTicket = new PurchasedTicket;
            $purchasedTicket->ticket_type_id = $reservedTicket->ticket_type_id;
            $purchasedTicket->user_id = $reservedTicket->user_id;
            $purchasedTicket->reserved_ticket_id = $reservedTicket->id;
            $purchasedTicket->save();
        }

        $email = $reservedTicket->user->email ?? $reservedTicket->email;

        if ($reservedTicket->isDirty('email')) {
            Mail::to($email)
                ->send(new ReservedTicketCreatedMail($reservedTicket, $reservedTicket->count ?? 1));
        }
    }

    /**
     * Reserved tickets can't be updated if a purchased ticket has been created
     */
    public function updating(ReservedTicket $reservedTicket): bool
    {
        if ($reservedTicket->is_purchased) {
            return false;
        }

        return true;
    }

    /**
     * Reserved tickets can't be deleted if a purchased ticket has been created
     */
    public function deleting(ReservedTicket $reservedTicket): bool
    {
        if ($reservedTicket->is_purchased) {
            return false;
        }

        return true;
    }
}
