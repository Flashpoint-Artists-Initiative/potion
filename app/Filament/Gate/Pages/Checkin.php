<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Models\Event;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

class Checkin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.gate.pages.checkin';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Check In';

    #[Url]
    public int $userId;

    #[Url]
    public int $eventId;

    /** @var array<string,array<string,string>> */
    public array $checklist;

    protected Event $event;

    protected User $user;

    public function mount(): void
    {
        $this->event = Event::findOrFail($this->eventId);
        $this->user = User::findOrFail($this->userId);

        $this->validateCheckin();
    }

    public function userInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(User::findOrFail($this->userId))
            ->schema([
                TextEntry::make('legal_name')
                    ->label('Legal Name'),
                TextEntry::make('email')
                    ->label('Email Address'),
                TextEntry::make('birthday')
                    ->label('Birthday'),
            ])
            ->columns(['sm' => 3]);
    }

    protected function validateCheckin(): void
    {
        // Check if the ticket is for the correct event
        $currentEventId = Event::getCurrentEventId();

        $correctEvent = $this->eventId === $currentEventId;
        // Check if the ticket is valid (not expired, not already used, etc.)
        $userTickets = $this->user->getValidTicketsForEvent($this->eventId);
        $hasValidTicket = $userTickets->isNotEmpty();
        $hasMultipleTickets = $userTickets->count() > 1;

        // Check if the user has signed the waiver
        $requiresWaiver = $this->event->waiver()->exists();
        $hasSignedWaiver = $this->user->hasSignedWaiverForEvent($this->eventId);

        if ($correctEvent && $hasValidTicket) {
            $this->checklist['ticket'] = [
                'color' => 'success',
                'message' => 'Participant has a valid ticket.',
            ];
        } else {
            $this->checklist['ticket'] = [
                'color' => 'danger',
                'message' => $correctEvent ? 'Participant does not have a valid ticket.' : 'Ticket is not valid for this event.',
            ];
        }

        if ($requiresWaiver) {
            if ($hasSignedWaiver) {
                $this->checklist['waiver'] = [
                    'color' => 'success',
                    'message' => 'Participant has signed the waiver.',
                ];
            } else {
                $this->checklist['waiver'] = [
                    'color' => 'danger',
                    'message' => 'Participant has not signed the waiver.',
                ];
            }
        } else {
            $this->checklist['waiver'] = [
                'color' => 'success',
                'message' => 'This event does not require a waiver.',
            ];
        }

        if ($hasMultipleTickets) {
            $this->checklist['multiple_tickets'] = [
                'color' => 'warning',
                'message' => 'Participant has multiple valid tickets.',
            ];
        }
    }

    public function viewTicketsAction(): Action
    {
        return Action::make('viewTickets')
            ->label('View Tickets')
            ->icon('heroicon-o-ticket')
            ->color('info')
            ->modalContent(view('filament.gate.modals.user-ticket-info', [
            ]))
            ->modalCancelAction(false)
            ->modalSubmitActionLabel('Close');
    }

    public function checkInAction(): Action
    {
        return Action::make('checkIn')
            ->label('Check In')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->form([
                TextInput::make('wristband_number')
                    ->label('Wristband Number')
                    ->required()
                    ->helperText('Put the wristband on the participant then enter the number here.'),
            ])
            ->action(function (array $data) {
                // TODO: Record the check-in in the database
            })
            ->disabled(collect($this->checklist)->contains(fn ($item) => $item['color'] === 'danger'));
    }
}
