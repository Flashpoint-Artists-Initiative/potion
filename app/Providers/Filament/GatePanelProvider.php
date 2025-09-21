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
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->authMiddleware([
                RedirectIfNotFilamentAdmin::class,
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Admin Site')
                    ->url(fn () => route('filament.admin.pages.dashboard'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->visible(fn (): bool => filament()->auth()->user()?->can('panelAccess.admin') ?? false)
                    ->sort(998),
                NavigationItem::make('Return to Main Site')
                    ->url(fn () => route('filament.app.pages.dashboard'))
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->sort(999),
            ])
            ->navigationGroups([
                'Event Specific',
                'Admin',
            ])
            ->plugins([
                // FilamentBanhammerPlugin::make(),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications();
    }
}
