<?php

declare(strict_types=1);

namespace App\Filament\Traits;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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
            ->dehydrateStateUsing(fn (?string $state) => $state ? trim($state) : null)
            ->autofocus();
    }

    protected function getPreferredNameFormComponent(): Component
    {
        return TextInput::make('preferred_name')
            ->label('Preferred Name')
            ->maxLength(255)
            ->dehydrateStateUsing(fn (?string $state) => $state ? trim($state) : null)
            ->helperText('If you don\'t want to use your legal name, what should we call you? Visible to event leadership and volunteer coordinators.');
    }

    protected function getBirthdayFormComponent(): Component
    {
        return DatePicker::make('birthday')
            ->closeOnDateSelection()
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
            ->unique(table: User::class, ignoreRecord: true);
    }

    protected function getPasswordFormComponent(bool $required = true): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required($required)
            ->rule(Password::default())
            ->dehydrateStateUsing(fn (?string $state) => $state ? Hash::make($state) : null)
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'))
            ->helperText('Your password must be at least 8 characters long and contain at least one letter and one number.');
    }

    protected function getPasswordConfirmationFormComponent(bool $required = true): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required($required)
            ->dehydrated(false);
    }
}
