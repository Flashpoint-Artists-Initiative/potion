<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectIfNotFilamentAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends CommonPanelProvider
{
    public string $id = 'admin';

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(fn () => asset('images/admin-logo-text.svg'))
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->databaseNotifications();
    }

    public function register(): void
    {
        parent::register();

        FilamentView::registerRenderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn (): string => Blade::render('@livewire(\'event-selector\')'));
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn (): string => Blade::render("@vite('resources/js/app.js')"));
        FilamentView::registerRenderHook(PanelsRenderHook::FOOTER,
            fn (): View => view('panel-footer'),
            // [Login::class, Register::class, RequestPasswordReset::class] // For now, show on every page
        );
    }
}
