<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ticketing\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NumberFormatter;

class OrderRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Order $order) {}

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
        $eventName = $this->order->event->name;
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $totalString = $formatter->formatCurrency($this->order->amount_total / 100, 'USD') ?: '$0.00';

        return (new MailMessage)
            ->from(config('mail.tickets.address'), config('mail.tickets.name'))
            ->subject('Your Order Has Been Refunded')
            ->line("Your order for the event '{$eventName}' has been refunded.")
            ->line("Total Refunded: $totalString")
            ->line('It may take a few days for the funds to appear in your account. Any tickets associated with this order have been voided.');
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
