<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\App\Clusters\UserPages\Pages\Tickets;
use App\Filament\App\Clusters\UserPages\Pages\TicketTransfers;
use App\Models\Ticketing\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OrderCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $eventName = $this->order->event->name;
        $ticketsUrl = Tickets::getUrl(panel: 'app');
        $transferUrl = TicketTransfers::getUrl(panel: 'app');
        $ticketEmail = config('mail.tickets.address');

        return (new MailMessage)
            ->from(config('mail.tickets.address'), config('mail.tickets.name'))
            ->subject("Order Confirmation for {$eventName}")
            ->greeting("You're all set for {$eventName}!")
            ->line("Log in to your {$appName} profile to view your tickets.")
            ->action('View my tickets', $ticketsUrl)
            ->line("Please note: Tickets this year are attached to your account. If you've bought multiple tickets, 
                every person in your group needs their own account to attend the event.")
            ->line(new HtmlString("Tickets can be transferred by going to the <a href=\"{$transferUrl}\">Ticket Transfers</a> section of your user profile."))
            ->salutation(' ')
            ->markdown('mail.order-completed-markdown', [
                'order' => $this->order,
                'helpText' => new HtmlString("For ticketing questions, contact <a href=\"mailto:{$ticketEmail}\">{$ticketEmail}</a>"),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
