<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectIfNotFilamentAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Gerenuk\FilamentBanhammer\FilamentBanhammerPlugin;

class GatePanelProvider extends CommonPanelProvider
{
    public string $id = 'gate';

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)
            ->path('gate')
            ->colors([
                'primary' => Color::Green,
            ])
            ->authMiddleware([
                RedirectIfNotFilamentAdmin::class,
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Return to Main Site')
                    ->url(fn () => route('filament.app.pages.dashboard'))
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->sort(999),
            ])
            ->navigationGroups([
                'Event Specific',
                'Admin',
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications();
    }
}
