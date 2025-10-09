<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Models\Event;
use App\Models\Ticketing\TicketType;
use App\Rules\ValidEmail;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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
                TextInput::make('email')
                    ->label('Email Address')
                    ->rule(new ValidEmail)
                    ->required()
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
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->in([$password])
                    ->validationMessages(['in' => 'The password is incorrect.'])
                    ->required(),
            ])
            ->action(function (array $data) {
                Notification::make()
                    ->title('Check-in Successful')
                    ->success()
                    ->body('Participant has been checked in successfully.')
                    ->send();
            });
    }
}
