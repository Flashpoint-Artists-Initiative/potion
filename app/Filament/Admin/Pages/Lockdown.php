<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\RolesEnum;
use App\Services\WebLockdownService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * @property Form $form
 */
class Lockdown extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.admin.pages.lockdown';

    protected static ?int $navigationSort = 20;

    public bool $lockdown;

    public ?string $lockdownBannerText;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole(RolesEnum::Admin) ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('lockdown')
                    ->label('Global Lockdown'),
                TextInput::make('lockdownBannerText')
                    ->label('Lockdown Banner Text')
                    ->placeholder('POTION is in read-only mode in order to move the data offline for the event.')
                    ->maxLength(255),
            ]);
    }

    public function mount(): void
    {
        $this->form->fill([
            'lockdown' =>Cache::get(WebLockdownService::GLOBAL_KEY, false),
            'lockdownBannerText' =>Cache::get(WebLockdownService::GLOBAL_TEXT_KEY, ''),
        ]);
    }

    public function submit(): void
    {
        Cache::put(WebLockdownService::GLOBAL_KEY, $this->form->getState()['lockdown']);
        Cache::put(WebLockdownService::GLOBAL_TEXT_KEY, $this->form->getState()['lockdownBannerText']);
        Notification::make()
            ->title('Lockdown Updated')
            ->success()
            ->send();
    }
}
