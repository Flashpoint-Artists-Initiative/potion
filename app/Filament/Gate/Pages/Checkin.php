<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Forms\Components\WaiverSignatureInput;
use App\Models\Event;
use App\Models\Ticketing\GateScan;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\TicketTransfer;
use App\Models\User;
use App\Rules\ValidEmail;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Actions;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Auth\Access\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Support\Enums\IconPosition;

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

    public ?int $wristbandNumber = null;

    protected Event $event;

    protected User $user;

    protected ?GateScan $latestScan;

    // since the original code does not have a type
    // @phpstan-ignore missingType.property
    protected $listeners = [
        'waiver-signed' => '$refresh'
    ];

    public function boot(): void
    {
        $this->event = Event::findOrFail($this->eventId);
        $this->user = User::findOrFail($this->userId);
        $this->latestScan = GateScan::where('user_id', $this->userId)
            ->where('event_id', $this->eventId)
            ->latest()
            ->first();

        $this->wristbandNumber = $this->latestScan?->wristband_number;
    }

    public function mount(): void
    {
        $this->validateCheckin();
    }

    public function userInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(User::findOrFail($this->userId))
            ->schema([
                TextEntry::make('legal_name')
                    ->label('Legal Name'),
                TextEntry::make('birthday')
                    ->label('Birthday')
                    ->date('n/j/Y'),
                TextEntry::make('email')
                    ->icon('heroicon-o-clipboard')
                    ->iconPosition(IconPosition::After)
                    ->label('Email Address')
                    ->copyable()
                    ->copyMessage('Email address copied to clipboard')
                    ->copyMessageDuration(1500),
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
        $transferableTickets = $userTickets->filter(fn(PurchasedTicket $ticket) => $ticket->ticketType->transferable);
        $hasValidTicket = $userTickets->isNotEmpty();
        $hasMultipleTickets = $userTickets->count() > 1;
        $hasTransferableTickets = $transferableTickets->isNotEmpty();

        // Check if user has already checked in
        /** @var GateScan|null $latestGateScan */
        $latestGateScan = GateScan::where('user_id', $this->userId)->where('event_id', $this->eventId)->latest()->first();

        // Check if the user has signed the waiver
        $requiresWaiver = $this->event->waiver()->exists();
        $hasSignedWaiver = $this->user->hasSignedWaiverForEvent($this->eventId);
        
        // Check ban list
        $isBanned = $this->user->isBanned();
        $banList = User::banned()->pluck('legal_name', 'id')->toArray();
        $highestBanMatch = 0;
        $highestBanId = null;
        
        foreach ($banList as $id => $name) {
            similar_text(strtolower($this->user->legal_name), strtolower($name), $percent);
            if ($percent > $highestBanMatch) {
                $highestBanMatch = $percent;
                $highestBanId = $id;
            }
        }

        if ($isBanned) {
            $this->checklist['ban'] = [
                'color' => 'danger',
                'message' => "Participant is banned from this event. Contact an event lead for assistance.",
            ];
        } elseif ($highestBanMatch >= 90) {
            $bannedUser = User::findOrFail($highestBanId);
            $this->checklist['ban'] = [
                'color' => 'warning',
                'message' => sprintf('Participant name is similar to banned user %s (%.2f%% match).', $bannedUser->legal_name, $highestBanMatch),
            ];
        }

        if ($correctEvent && $hasValidTicket) {
            if ($latestGateScan) {
                $this->checklist['ticket'] = [
                    'color' => 'danger',
                    'message' => sprintf(
                        'Participant has already checked in. Wristband #%s issued at %s.',
                        $latestGateScan->wristband_number,
                        $latestGateScan->created_at?->setTimezone('America/New_York')->format('n/j/Y g:i a') ?? 'Unknown time'
                    ),
                ];
            } else {
                $this->checklist['ticket'] = [
                    'color' => 'success',
                    'message' => 'Participant has a valid ticket.',
                ];
            }
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
                'color' => $hasTransferableTickets ? 'info' : 'warning',
                'message' => sprintf('Participant has %d valid tickets. %d %s transferable.', 
                    $userTickets->count(), 
                    $transferableTickets->count(), 
                    $transferableTickets->count() === 1 ? 'is' : 'are'
                ),
            ];
        }
    }

    public function transferTicketsAction(): Action
    {
        $nextTicket = $this->getNextTransferableTicket();

        if (! $nextTicket) {
            return Action::make('transferTickets')
                ->label('No transferable tickets available.')
                ->icon('heroicon-o-ticket')
                ->color('warning')
                ->disabled();
        }

        return Action::make('transferTickets')
            ->label('Transfer Tickets')
            ->icon('heroicon-o-ticket')
            ->color('info')
            ->form(fn(Form $form) => $this->transferForm($form))
            ->modalCancelAction(false)
            ->modalSubmitActionLabel('Select')
            ->action(function (array $data) use ($nextTicket) {
                /** @var User $receivingUser */
                $receivingUser = User::findOrFail($data['user_id']);
                /** @var PurchasedTicket $nextTicket */
                TicketTransfer::createTransfer($this->user->id, $receivingUser->email, [$nextTicket->id], quietly: true)->complete('Transferred to existing user at check-in.');
                $this->redirect(self::getUrl([
                    'userId' => $receivingUser->id,
                    'eventId' => $this->eventId,
                ]));
                Notification::make()
                    ->title('Ticket Transferred Successfully')
                    ->success()
                    ->body("A ticket has been transferred to {$receivingUser->legal_name}.")
                    ->send();
            });
    }

    public function checkInAction(): Action
    {
        $canCheckIn = collect($this->checklist)->contains(fn($item) => $item['color'] === 'danger') === false;

        return Action::make('checkIn')
            ->label('Check In')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->form([
                TextInput::make('wristband_number')
                    ->label('Wristband Number')
                    ->required()
                    ->helperText('Put the new wristband on the participant then enter the number here.'),
            ])
            ->action(function (array $data) {
                GateScan::create([
                    'user_id' => $this->userId,
                    'event_id' => $this->eventId,
                    'wristband_number' => $data['wristband_number'],
                ]);

                Notification::make()
                    ->title('Check-in Successful')
                    ->success()
                    ->body('Participant has been checked in successfully.')
                    ->send();
            })
            ->color(fn() => $canCheckIn ? 'success' : 'danger')
            ->hidden(!$canCheckIn);
    }

    public function updateWristbandAction(): Action
    {
        return Action::make('updateWristband')
            ->label('Update Wristband Number')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->form([
                TextInput::make('wristband_number')
                    ->label('Wristband Number')
                    ->required()
                    ->helperText('Put the wristband on the participant then enter the number here.'),
            ])
            ->action(function (array $data) {
                GateScan::create([
                    'user_id' => $this->userId,
                    'event_id' => $this->eventId,
                    'wristband_number' => $data['wristband_number'],
                ]);

                Notification::make()
                    ->title('Wristband Updated Successfully')
                    ->success()
                    ->body('Participant\'s wristband number has been updated successfully.')
                    ->send();
            });
    }

    protected function transferForm(Form $form): Form
    {
        $nextTicket = $this->getNextTransferableTicket();
        $ticketCount = $this->user->getValidTicketsForEvent($this->eventId)->count();

        if ($nextTicket === null) {
            return $form->schema([
                Placeholder::make('input')
                    ->state('No transferable tickets available.'),
            ]);
        }

        return $form
            ->schema([
                Placeholder::make('warning')
                    ->hiddenLabel()
                    ->visible($ticketCount <= 1)
                    ->content(new HtmlString('<h1 class="text-2xl">This user has only one ticket. Transferring it will leave them without a ticket.</h1>')),
                Select::make('user_id')
                    ->label('Select Attendee')
                    ->searchable()
                    ->live()
                    ->required()
                    ->getSearchResultsUsing(function (string $search) {
                        return User::where('legal_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->select(DB::raw("id, CONCAT(legal_name, ' (', email, ')') AS legal_name"))
                        ->limit(50)
                        ->pluck('legal_name', 'id')
                        ->toArray();
                    })
                    ->autofocus()
                    ->hintAction($this->createNewUserAction()),
            ]);
    }

    protected function createNewUserAction(): FormAction
    {
        return FormAction::make('createNewUser')
            ->label('Create New User')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->form(fn(Form $form) => $this->createNewUserForm($form))
            ->action(function (array $data, Set $set) {
                if ($user = User::where('email', $data['email'])->first()) {

                    Notification::make()
                        ->title('User Found')
                        ->success()
                        ->body("User {$user->legal_name} was found and will be used for the transfer.")
                        ->send();
                    $note = 'Transferred to existing user created at check-in.';
                } else {
                    $user = User::create($data);

                    Notification::make()
                        ->title('User Created Successfully')
                        ->success()
                        ->body("User {$user->legal_name} has been created successfully.")
                        ->send();
                    $note = 'Transferred to new user created at check-in.';
                }

                $nextTicket = $this->getNextTransferableTicket();
                if ($nextTicket === null) {
                    Notification::make()
                        ->title('No Transferable Tickets')
                        ->warning()
                        ->body("User {$this->user->legal_name} does not have any transferable tickets.")
                        ->send();
                    return; 
                }

                TicketTransfer::createTransfer($this->user->id, $user->email, [$nextTicket->id], quietly: true)->complete($note);

                Notification::make()
                    ->title('Ticket Transferred Successfully')
                    ->success()
                    ->body("A ticket has been transferred to {$user->legal_name}.")
                    ->send();

                $this->redirect(self::getUrl([
                    'userId' => $user->id,
                    'eventId' => $this->eventId,
                ]));
            });
    }

    protected function createNewUserForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('legal_name')
                    ->label('Legal Name')
                    ->required()
                    ->autofocus(),
                DatePicker::make('birthday')
                    ->label('Birthday')
                    ->date()
                    ->required(),
                TextInput::make('email')
                    ->label('Email Address')
                    ->rule(new ValidEmail)
                    ->required(),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->rule(new Password(config('auth.password_min_length')))
                    ->default(fn() => str()->random(12))
                    ->helperText('A random 12 character password has been generated. You may change it if you wish.'),
            ]);
    }

    public function signWaiverAction(): Action
    {
        $requiresWaiver = $this->event->waiver()->exists();
        $hasSignedWaiver = $this->user->hasSignedWaiverForEvent($this->eventId);
        $waiver = $this->event->waiver;

        return Action::make('signWaiver')
            ->label('Have User Sign Waiver')
            ->color('warning')
            ->visible($requiresWaiver && ! $hasSignedWaiver)
            ->action(fn (array $data) => $this->createCompletedWaiver($data))
            ->modalHeading('Sign Waiver')
            ->modalWidth(MaxWidth::FiveExtraLarge)
            ->form([
                Placeholder::make('title')
                    ->content(new HtmlString('<h1 class="text-2xl">' . ($waiver->title ?? '') . '</h1>'))
                    ->label('')
                    ->dehydrated(false),
                Placeholder::make('waiver')
                    ->content(new HtmlString($waiver->content ?? ''))
                    ->label('')
                    ->dehydrated(false),
                WaiverSignatureInput::makeWithUser('signature', $this->userId),
            ]);
    }

    /**
     * @param  array<string, string>  $data
     */
    protected function createCompletedWaiver(array $data): void
    {
        if ($waiver = Event::getCurrentEvent()?->waiver) {
            $waiver->completedWaivers()->create([
                'user_id' => $this->userId,
                'form_data' => [
                    'signature' => $data['signature'],
                ],
            ]);
        }

        // TODO: replace this hack with a proper way to update the component
        $this->js('window.location.reload()'); 
    }

    protected function getNextTransferableTicket(): ?PurchasedTicket
    {
        $tickets = $this->user->getValidTicketsForEvent($this->eventId);
        $transferableTickets = $tickets->filter(fn(PurchasedTicket $ticket) => $ticket->ticketType->transferable);
        $checkedIn = $this->user->gateScans()->currentEvent()->exists();

        $canTransfer = true;

        // Must have at least one transferable ticket, and if checked in must have more than one ticket
        if ($tickets->isEmpty() ||
            $transferableTickets->isEmpty() ||
            ($checkedIn && $tickets->count() < 2)
        ) {
            $canTransfer = false;
        }

        return $canTransfer ? $transferableTickets->first() : null;
    }
}
