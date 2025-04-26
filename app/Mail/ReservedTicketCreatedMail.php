<?php

declare(strict_types=1);

namespace App\Mail;

use App\Filament\App\Clusters\UserPages\Pages\Tickets;
use App\Models\Ticketing\ReservedTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class ReservedTicketCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected ReservedTicket $reservedTicket)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $eventName = $this->reservedTicket->ticketType->event->name;
        $count = $this->reservedTicket->count ?? 1;
        $price = $this->reservedTicket->ticketType->price;
        
        $subject = sprintf('%s: You have been granted %s %s %s', 
            $eventName, 
            $count > 1 ? $count : 'a',
            $price === 0 ? 'free' : 'reserved',
            str('ticket')->plural($count)
        );

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
        if ($this->reservedTicket->ticketType->price === 0) {
            return $this->freeContent();
        }

        return $this->reservedContent();
    }

    protected function freeContent(): Content
    {
        $eventName = $this->reservedTicket->ticketType->event->name;
        $ticketName = $this->reservedTicket->ticketType->name;
        $count = $this->reservedTicket->count ?? 1;
        $ticketWord = str('ticket')->plural($count);
        $isWord = $count > 1 ? 'are' : 'is';
        $url = Tickets::getUrl(panel: 'app');
        $potionUrl = url('');

        $message = (new MailMessage)
            ->greeting("Your {$ticketWord} for {$eventName} {$isWord} ready!")
            ->line(new HtmlString("You've received <b>{$count}</b> free <b>{$ticketName}</b> {$ticketWord}."))
            ->action("View your {$ticketWord}", $url)
            ->line('If you already have an account, click the link and login to view your ticket.')
            ->line("Otherwise, you'll be prompted to create an account before continuing.  Be sure to use this email address to create your account.")
            ->lineIf($count > 1, new HtmlString("Please note that each {$eventName} attendee must have their own <a href=\"{$potionUrl}\">POTION</a> account.  You can transfer your additional tickets to other people after they've created their accounts."))
            ->salutation(' ');

        return new Content(
            htmlString: (string) $message->render(),
        );
    }

    protected function reservedContent(): Content
    {
        $eventName = $this->reservedTicket->ticketType->event->name;
        $ticketName = $this->reservedTicket->ticketType->name;
        $ticketNote = $this->reservedTicket->note;
        $count = $this->reservedTicket->count ?? 1;
        $ticketWord = str('ticket')->plural($count);
        $isWord = $count > 1 ? 'are' : 'is';
        $expirationDate = $this->reservedTicket->final_expiration_date->format('l, F jS, Y \a\t g:ia');
        $price = '$' . $this->reservedTicket->ticketType->price;
        $url = Tickets::getUrl(panel: 'app');
        $potionUrl = url('');

        $message = (new MailMessage)
            ->greeting("Your reserved {$ticketWord} for {$eventName} {$isWord} waiting for you!")
            ->line(new HtmlString("You've been granted <b>{$count}</b> reserved {$ticketWord}."))
            ->line(new HtmlString("<b>{$count}x {$ticketName} - {$price}</b>"))
            ->lineif(!empty($ticketNote), new HtmlString("With Note: <i>{$ticketNote}</i>"))
            ->action("View your reserved {$ticketWord}", $url)
            ->line(new HtmlString("Your reserved {$ticketWord} will <b>expire</b> on <b>{$expirationDate}</b>."))
            ->line('If you already have an account, click the link and login to view your reserved ticket.')
            ->line("Otherwise, you'll be prompted to create an account before continuing.  Be sure to use this email address to create your account.")
            ->lineIf($count > 1, new HtmlString("Please note that each {$eventName} attendee must have their own <a href=\"{$potionUrl}\">POTION</a> account.  You can transfer your additional tickets to other people after they've created their accounts."))
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
