<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Models\Event;
use App\Models\Ticketing\CompletedWaiver;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\TicketTransfer;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use App\Rules\ValidEmail;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationsAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;

class GateAdmin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.gate.pages.gate-admin';

    protected static ?string $title = 'Gate Admin';

    protected static ?int $navigationSort = 5;

    public function createTicketAction(): Action
    {
        $password = config('app.gate_admin_password');

        return Action::make('createTicket')
            ->label('Create Ticket')
            ->icon('heroicon-o-plus')
            ->color('success')
            ->form([
                TextInput::make('password')
                    ->label('Admin Password')
                    ->password()
                    ->in([$password])
                    ->validationMessages(['in' => 'The password is incorrect.'])
                    ->required(),
                TextInput::make('email')
                    ->label('Email Address')
                    ->rule(new ValidEmail)
                    ->required()
                    ->exists(User::class, 'email', fn(Exists $rule) => $rule->whereNull('deleted_at'))
                    ->validationMessages(['exists' => 'No user with that email address was found.'])
                    ->autofocus(),
                Select::make('ticket_type')
                    ->label('Ticket Type')
                    ->options(TicketType::where([
                        'event_id' => Event::getCurrentEventId(),
                        'transferable' => false,
                        'addon' => false,
                        'price' => 0,
                    ])->pluck('name', 'id'))
                    ->required(),
            ])
            ->action(function (array $data) {
                $user = User::where('email', $data['email'])->firstOrFail();
                $ticketType = TicketType::firstOrFail($data['ticket_type']);
                PurchasedTicket::create([
                    'user_id' => $user->id,
                    'ticket_type_id' => $ticketType->id,
                ]);
                Notification::make()
                    ->title('Ticket Created')
                    ->success()
                    ->body('A ticket has been created for ' . $user->legal_name . '.')
                    ->actions([
                        NotificationsAction::make('go_to_user')
                            ->label('Go to User')
                            ->button()
                            ->url(Checkin::getUrl(['userId' => $user->id, 'eventId' => Event::getCurrentEventId()]))
                    ])
                    ->send();
            });
    }

    public function createUserAction(): Action
    {
        return Action::make('createUser')
            ->label('Create New User')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->form(fn(Form $form) => $this->createNewUserForm($form))
            ->action(function (array $data) {
                if ($user = User::where('email', $data['email'])->first()) {

                    Notification::make()
                        ->title('User Already Exists')
                        ->info()
                        ->body("User {$user->legal_name} already exists.")
                        ->actions([
                            NotificationsAction::make('go_to_user')
                                ->label('Go to User')
                                ->button()
                                ->url(Checkin::getUrl(['userId' => $user->id, 'eventId' => Event::getCurrentEventId()]))
                        ])
                        ->send();
                } else {
                    $user = User::create($data);

                    Notification::make()
                        ->title('User Created Successfully')
                        ->success()
                        ->body("User {$user->legal_name} has been created successfully.")
                        ->actions([
                            NotificationsAction::make('go_to_user')
                                ->label('Go to User')
                                ->button()
                                ->url(Checkin::getUrl(['userId' => $user->id, 'eventId' => Event::getCurrentEventId()]))
                        ])
                        ->send();
                }
            });
    }

    protected function createNewUserForm(Form $form): Form
    {
        $password = config('app.gate_admin_password');

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
                    ->label('User Password')
                    ->password()
                    ->required()
                    ->rule(new Password(config('auth.password_min_length')))
                    ->default(fn() => str()->random(12))
                    ->helperText('A random 12 character password has been generated. You may change it if you wish.'),
            ]);
    }

    public function signPaperWaiverAction(): Action
    {
        return Action::make('signPaperWaiver')
            ->label('Sign Paper Waiver')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->button()
            ->form([
                TextInput::make('email')
                    ->label('Email Address')
                    ->rule(new ValidEmail)
                    ->required()
                    ->exists(User::class, 'email', fn(Exists $rule) => $rule->whereNull('deleted_at'))
                    ->validationMessages(['exists' => 'No user with that email address was found.'])
                    ->autofocus(),
            ])
            ->action(function (array $data) {
                $user = User::where('email', $data['email'])->firstOrFail();
                $event = Event::getCurrentEvent();

                if (! $event) {
                    Notification::make()
                        ->title('No Current Event')
                        ->danger()
                        ->body('There is no current event set. Please set a current event before signing waivers.')
                        ->send();
                    return;
                }

                $waiver = $event->waiver;

                if (!$waiver) {
                    Notification::make()
                        ->title('No Waiver Found')
                        ->warning()
                        ->body('This event does not require a waiver.')
                        ->send();
                    return;
                }

                $completedWaiver = CompletedWaiver::where([
                    'user_id' => $user->id,
                    'waiver_id' => $event->id,
                ])->first();

                if ($completedWaiver) {
                    Notification::make()
                        ->title('Waiver Already Signed')
                        ->danger()
                        ->body("{$user->legal_name} has already signed the waiver.")
                        ->actions([
                            NotificationsAction::make('go_to_user')
                                ->label('Go to User')
                                ->button()
                                ->url(Checkin::getUrl(['userId' => $user->id, 'eventId' => Event::getCurrentEventId()]))
                        ])
                        ->send();
                    return;
                }

                CompletedWaiver::create([
                    'user_id' => $user->id,
                    'waiver_id' => Event::getCurrentEventId(),
                    'paper_completion' => true,
                ]);

                Notification::make()
                    ->title('Waiver Signed')
                    ->success()
                    ->body("{$user->legal_name}'s waiver has been marked as signed.")
                    ->actions([
                        NotificationsAction::make('go_to_user')
                            ->label('Go to User')
                            ->button()
                            ->url(Checkin::getUrl(['userId' => $user->id, 'eventId' => Event::getCurrentEventId()]))
                    ])
                    ->send();
            });
    }
}
