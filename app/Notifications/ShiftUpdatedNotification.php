<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Volunteering\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ShiftUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array<string, array<string, scalar>>  $changes
     */
    public function __construct(protected Shift $shift, protected array $changes, protected ?string $reason = null) {}

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
        $message = (new MailMessage)
            ->subject(sprintf('One of your %s shifts has been changed', $this->shift->team->event->name))
            ->greeting('Hello!')
            ->line(sprintf('One of your volunteer shifts for %s has been changed:', $this->shift->team->event->name))
            ->line(new HtmlString(sprintf('<strong>%s</strong> on <strong>%s</strong>',
                $this->shift->title,
                $this->shift->startCarbon->format('D F jS, Y g:i A T')
            )));

        foreach ($this->changes as $change) {
            $message->line(new HtmlString(sprintf('%s: from <strong>%s</strong> to <strong>%s</strong>',
                $change['label'],
                $change['old'],
                $change['new']
            )));
        }

        $message->lineIf(! is_null($this->reason), sprintf('Reason: %s', $this->reason))
            ->action('View your volunteer schedule', url('/'));

        return $message;
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
