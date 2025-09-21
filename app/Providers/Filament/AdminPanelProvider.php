<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\LockdownEnum;
use App\Http\Middleware\RedirectIfNotFilamentAdmin;
use App\Services\WebLockdownService;
use Filament\Http\Middleware\Authenticate;
use Filament\Livewire\DatabaseNotifications;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Gerenuk\FilamentBanhammer\FilamentBanhammerPlugin;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

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
                FilamentBanhammerPlugin::make(),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications()
            ->databaseNotificationsPolling(fn() => config('app.debug') === true ? null : 30);
    }

    public function register(): void
    {
        parent::register();

        // Event Selector
        FilamentView::registerRenderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn (): string => Blade::render('@livewire(\'event-selector\')'));

        // Custom CSS and JS
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn (): string => Blade::render("@vite('resources/js/app.js')"));

        // Footer Links
        FilamentView::registerRenderHook(PanelsRenderHook::FOOTER,
            fn (): View => view('panel-footer'),
            // [Login::class, Register::class, RequestPasswordReset::class] // For now, show on every page
        );

        // Lockdown Banner
        FilamentView::registerRenderHook(PanelsRenderHook::CONTENT_START,
            function (): ?string {
                // Only using one global lockdown for now, change this if we need to use multiple lockdowns
                $isLocked = LockdownEnum::Tickets->isLocked() && config('app.use_single_lockdown');
                if ($isLocked) {
                    return Blade::render('<x-notification-banner color="danger" class="mt-2">' .
                        Cache::get(WebLockdownService::GLOBAL_TEXT_KEY, 'POTION is in read-only mode in order to move the data offline for the event.') .
                    '</x-notification-banner>');
                }

                return null;
            });
    }
}
