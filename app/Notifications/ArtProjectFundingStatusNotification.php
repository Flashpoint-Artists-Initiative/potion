<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Admin\Resources\ArtProjectResource;
use App\Models\Grants\ArtProject;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtProjectFundingStatusNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ArtProject $project,
        public bool $isOwner,
    ) {
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
        $fundingStatus = $this->project->fundingStatus;
        $amount = $this->project->{$fundingStatus->getAmountField()};
        $name = $this->project->name;
        $eventName = $this->project->event->name;
        $numVotes = $this->project->totalVotes;

        // For project owner
        if ($this->isOwner) {
            return (new MailMessage)
                ->subject('Your Art Project Funding Status has Changed')
                ->greeting('Hello ' . $this->project->user->display_name)
                ->salutation('Congratulations!')
                ->line('Project Name: ' . $name)
                ->line('New Funding Status: ' . $fundingStatus->getLabel())
                ->line('Funding Amount: $' . $amount)
                ->line('Once voting is complete, you will receive an email with the final funding amount and instructions on how to accept your grant funds.');
        }

        // For admin
        return (new MailMessage)
            ->subject("An Art Project's Funding Status has Changed")
            ->greeting(' ')
            ->salutation(' ')
            ->line('Project Name: ' . $name)
            ->line('New Funding Status: ' . $fundingStatus->getLabel())
            ->line('Funding Amount: $' . $amount)
            ->line('Total Votes: ' . $numVotes)
            ->action('View Project', ArtProjectResource::getUrl('view', [
                'record' => $this->project->id,
            ], panel: 'admin'));
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
