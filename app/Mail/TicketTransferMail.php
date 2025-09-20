<?php

declare(strict_types=1);

namespace App\Mail;

use App\Filament\App\Clusters\UserPages\Pages\TicketTransfers;
use App\Models\Ticketing\TicketTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TicketTransferMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected TicketTransfer $ticketTransfer)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $eventName = $this->ticketTransfer->event->name;

        $subject = $eventName . ': Pending Ticket Transfer';

        return new Envelope(
            from: new Address(config('mail.tickets.address'), config('mail.tickets.name')),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $eventName = $this->ticketTransfer->event->name;
        $ticketString = Str::plural('Ticket', $this->ticketTransfer->ticketCount);
        $url = TicketTransfers::getUrl(panel: 'admin');

        $message = (new MailMessage)
            ->line('You have a pending ticket transfer for ' . $eventName)
            ->action('Click Here to Accept your ' . $ticketString, $url)
            ->line('If you already have an account, click the link and login to accept your ' . strtolower($ticketString))
            ->line("Otherwise, you'll be prompted to create an account before continuing.  Be sure to use this email address to create your account.")
            ->salutation(' ');

        return new Content(
            htmlString: (string) $message->render(),
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
