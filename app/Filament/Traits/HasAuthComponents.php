<?php

declare(strict_types=1);

namespace App\Filament\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Livewire\Attributes\Url;

trait HasAuthComponents
{
    #[Url]
    public string $email = '';

    protected function getLegalNameFormComponent(): Component
    {
        return TextInput::make('legal_name')
            ->label('Full Legal Name')
            ->required()
            ->maxLength(255)
            ->helperText('As it shows up on your ID.  This will only be visible to gate staff as you enter the event.')
            ->autofocus();
    }

    protected function getPreferredNameFormComponent(): Component
    {
        return TextInput::make('preferred_name')
            ->label('Preferred Name')
            ->maxLength(255)
            ->helperText('If you don\'t want to use your legal name, what should we call you? Visible to event leadership and volunteer coordinators.');
    }

    protected function getBirthdayFormComponent(): Component
    {
        return DatePicker::make('birthday')
            ->required()
            ->before('18 years ago today')
            ->validationMessages([
                'before' => 'You must be at least 18 years old to create an account.',
            ])
            ->displayFormat('d/m/Y')
            ->helperText('Used to verify your identity when entering the event.');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email address')
            ->email()
            ->required()
            ->default($this->email)
            ->maxLength(255)
            ->helperText('If you are creating an account to accept a ticket transfer or a directed sale ticket, please use the email address the transfer was sent to. You can always change it later.')
            ->unique(ignoreRecord: true);
    }
}
