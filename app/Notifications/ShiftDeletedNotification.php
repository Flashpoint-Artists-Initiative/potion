<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Volunteering\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ShiftDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Shift $shift) {}

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
        return (new MailMessage)
            ->subject(sprintf('One of your %s shifts has been deleted', $this->shift->team->event->name))
            ->greeting('Hello!')
            ->line(sprintf('One of your volunteer shifts for %s has been deleted:', $this->shift->team->event->name))
            ->line(new HtmlString(sprintf('<strong>%s</strong> on <strong>%s</strong>',
                $this->shift->title,
                $this->shift->startCarbon->format('D F jS, Y g:i A T')
            )))
            ->action('View your volunteer schedule', url('/'));
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
