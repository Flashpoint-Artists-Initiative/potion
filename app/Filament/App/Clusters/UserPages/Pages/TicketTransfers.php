<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Enums\LockdownEnum;
use App\Filament\App\Clusters\UserPages;
use App\Models\Event;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketTransfer;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

/**
 * @property Form $form
 */
class TicketTransfers extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string $view = 'filament.app.clusters.user-pages.pages.ticket-transfers';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = UserPages::class;

    /** @var array<string, mixed> */
    public array $data = [];

    // Prefilling data based on query string
    #[Url]
    public ?int $purchased = null;

    #[Url]
    public ?int $reserved = null;

    public bool $showWaiverWarning;

    public bool $lockdownEnabled;

    #[On('active-event-updated')]
    public function mount(): void
    {
        $waiver = Event::getCurrentEvent()?->waiver;
        $hasTransfers = TicketTransfer::query()->involvesUser()->exists();
        $hasSignedWaiver = (Auth::user()?->hasSignedWaiverForEvent(Event::getCurrentEventId()) ?? false);

        $this->showWaiverWarning = $waiver && $hasTransfers && ! $hasSignedWaiver;

        $this->lockdownEnabled = LockdownEnum::Tickets->isLocked();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->newTransferAction(),
        ];
    }

    public function newTransferAction(): Action
    {
        $purchasedTickets = PurchasedTicket::query()->currentUser()->currentEvent()->canBeTransferred()->get();
        $reservedTickets = ReservedTicket::query()->currentUser()->currentEvent()->canBePurchased()->canBeTransferred()->get();

        $purchasedItems = $purchasedTickets->mapWithKeys(function (PurchasedTicket $ticket, int $key) {
            $label = sprintf('%s (#%d)', $ticket->ticketType->name, $ticket->id);

            return [$ticket->id => $label];
        });

        $reservedItems = $reservedTickets->mapWithKeys(function (ReservedTicket $ticket, int $key) {
            $label = sprintf('%s (#%d)', $ticket->ticketType->name, $ticket->id);

            return [$ticket->id => $label];
        });

        return Action::make('newTransfer')
            ->label('Start a new Ticket Transfer')
            ->modal()
            ->form([
                Section::make([
                    TextInput::make('recipient_email')
                        ->email()
                        ->required()
                        ->columnSpan('full'),
                    Select::make('purchased_tickets')
                        ->requiredWithout('reserved_tickets')
                        ->rule(fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $tickets = PurchasedTicket::findMany($value)->each(function (PurchasedTicket $ticket) use ($fail) {
                                if ($ticket->user_id != Auth::id()) {
                                    $fail('All tickets must belong to you to transfer');
                                }
                            });
                        })
                        ->validationMessages(['required_without' => 'You must select at least one ticket to transfer'])
                        ->multiple()
                        ->searchable(false)
                        ->placeholder("Select which purchased ticket(s) you'd like to transfer")
                        ->default($this->purchased && $purchasedItems->has($this->purchased) ? [$this->purchased] : null)
                        ->options($purchasedItems)
                        ->columnSpan(1),
                    Select::make('reserved_tickets')
                        ->requiredWithout('purchased_tickets')
                        ->rule(fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $tickets = ReservedTicket::findMany($value)->each(function (ReservedTicket $ticket) use ($fail) {
                                if ($ticket->user_id != Auth::id()) {
                                    $fail('All tickets must belong to you to transfer');
                                }
                            });
                        })
                        ->validationMessages(['required_without' => 'You must select at least one ticket to transfer'])
                        ->multiple()
                        ->searchable(false)
                        ->placeholder("Select which reserved ticket(s) you'd like to transfer")
                        ->default($this->reserved && $reservedItems->has($this->reserved) ? [$this->reserved] : null)
                        ->options($reservedItems)
                        ->columnSpan(1),
                ])
                    ->columns(2),
                ViewField::make('warning')
                    ->view('filament.app.modals.ticket-transfer-confirmation'),
            ])
            ->modalAutofocus(false)
            ->action(fn (array $data) => $this->createTransfer($data))
            ->visible(fn () => ! $this->lockdownEnabled && Event::getCurrentEvent()?->endDateCarbon->isFuture());
    }

    /**
     * @param  array<mixed>  $data
     */
    public function createTransfer(array $data): void
    {
        try {
            TicketTransfer::createTransfer((int) Auth::id(), $data['recipient_email'], $data['purchased_tickets'], $data['reserved_tickets']);
            Notification::make()
                ->title('Transfer Created')
                ->success()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Transfer Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $eventIsFuture = Event::getCurrentEvent()?->endDateCarbon->isFuture();

        return $table
            ->query(TicketTransfer::query()->specificEvent()->involvesUser())
            ->columns([
                TextColumn::make('completed')
                    ->badge()
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state) => ! $eventIsFuture && ! $state ? 'Expired' : ($state ? 'Completed' : 'Pending'))
                    ->color(fn (bool $state) => ! $eventIsFuture && ! $state ? 'danger' : ($state ? 'success' : 'warning'))
                    ->grow(false),
                TextColumn::make('ticketCount')
                    ->formatStateUsing(function (TicketTransfer $record) {
                        $purchased = $record->purchasedTickets->count();
                        $reserved = $record->reservedTickets->count();
                        $linebreak = ($purchased && $reserved) ? '<br>' : '';

                        return new HtmlString(sprintf('%s%s%s',
                            $purchased ? $purchased . ' Ticket' : '',
                            $linebreak,
                            $reserved ? $reserved . ' Reserved' : '')
                        );
                    }),
                TextColumn::make('recipient_email')
                    ->label('Sent To')
                    ->formatStateUsing(fn (string $state, TicketTransfer $record) => new HtmlString($record->user_id == Auth::id() ? $state : 'You')),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York'),
            ])
            ->emptyStateHeading('You have no ticket transfers')
            ->defaultSort('completed')
            ->actions([
                TableAction::make('accept')
                    ->button()
                    ->action(fn (TicketTransfer $record) => $record->complete())
                    ->visible(function (TicketTransfer $record) use ($eventIsFuture) {
                        /** @var User $user */
                        $user = Auth::user();

                        return ! $this->lockdownEnabled && $eventIsFuture && $user->can('complete', $record);
                    })
                    ->color(fn () => $this->showWaiverWarning ? 'gray' : null)
                    ->disabled(fn () => $this->showWaiverWarning),
            ])
            ->actionsPosition(ActionsPosition::BeforeCells);
    }

    public function signWaiverAction(): Action
    {
        /** @var User */
        $user = Auth::user();
        $username = $user->legal_name;

        $waiver = Event::getCurrentEvent()?->waiver;

        return Action::make('signWaiver')
            ->label('sign a waiver')
            ->link()
            ->size('')
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
                TextInput::make('signature')
                    ->label('I agree to the terms of the waiver and understand that I am signing this waiver electronically.')
                    ->helperText('You must enter your full legal name as it is shown on your ID and listed in your profile.')
                    ->required()
                    ->placeholder($username)
                    ->in([$username])
                    ->validationMessages([
                        'required' => 'You must agree to the terms of the waiver and sign it.',
                        'in' => 'The entered value must match your legal name, as listed in your profile.',
                    ]),
            ]);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function createCompletedWaiver(array $data): void
    {
        if ($waiver = Event::getCurrentEvent()?->waiver) {
            $waiver->completedWaivers()->create([
                'user_id' => Auth::id(),
                'form_data' => [
                    'signature' => $data['signature'],
                ],
            ]);

            $this->showWaiverWarning = false;
        }
    }
}
