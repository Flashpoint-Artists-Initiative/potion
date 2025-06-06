<?php

declare(strict_types=1);

namespace Tests\Unit\Ticketing;

use App\Models\Event;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketTransfer;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketTransferTest extends TestCase
{
    #[Test]
    public function second_completion(): void
    {
        $transfer = TicketTransfer::factory()->create();
        $ticket = PurchasedTicket::factory()->create();
        $transfer->purchasedTickets()->attach($ticket);

        $firstUser = $ticket->user;
        $secondUser = User::factory()->create(['email' => $transfer->recipient_email]);

        $this->assertEquals(0, $transfer->completed);
        $this->assertEquals($firstUser->id, $ticket->user_id);

        $transfer->complete();
        $ticket->refresh();
        $transfer->refresh();

        $this->assertEquals(1, $transfer->completed);
        $this->assertEquals($secondUser->id, $ticket->user_id);

        // After transfer, move the ticket back to the original user
        $ticket->user_id = $firstUser->id;
        $ticket->saveQuietly();

        $transfer->complete();
        $ticket->refresh();
        $transfer->refresh();

        // Check that the ticket didn't change owners again
        $this->assertEquals(1, $transfer->completed);
        $this->assertEquals($firstUser->id, $ticket->user_id);
    }

    #[Test]
    public function recipient_relation(): void
    {
        $transfer = TicketTransfer::factory()->create();
        $recipient = User::factory()->create(['email' => $transfer->recipient_email]);

        $this->assertEquals($transfer->recipient?->id, $recipient->id);
    }

    #[Test]
    public function update_fails(): void
    {
        $transfer = TicketTransfer::factory()->create();
        $email = $transfer->recipient_email;
        $newEmail = 'newEmail@test.com';

        $this->assertNotEquals($email, $newEmail);

        $success = $transfer->update(['recipient_email' => $newEmail]);

        $this->assertFalse($success);

        $transfer->refresh();

        $this->assertNotEquals($transfer->recipient_email, $newEmail);
    }

    #[Test]
    public function event_attribute(): void
    {
        $transfer = TicketTransfer::factory()->create();
        $reservedTicket = ReservedTicket::factory()->create();
        $purchasedTicket = PurchasedTicket::factory()->create();

        $transfer->reservedTickets()->attach($reservedTicket->id);

        $this->assertInstanceOf(Event::class, $transfer->event);

        $transfer = TicketTransfer::factory()->create();
        $transfer->purchasedTickets()->attach($purchasedTicket->id);

        $this->assertInstanceOf(Event::class, $transfer->event);
    }
}
