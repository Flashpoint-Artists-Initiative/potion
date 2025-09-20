<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Register;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets;

class AppPanelProvider extends CommonPanelProvider
{
    public string $id = 'app';

    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)
            ->default()
            ->spa()
            ->spaUrlExceptions([
                '*/admin',
                '*/admin/*',
            ])
            ->path('')
            ->login()
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification()
            // ->profile(EditProfile::class, isSimple: false)
            ->colors([
                'primary' => Color::Violet,
            ])
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Admin Site')
                    ->url(fn () => route('filament.admin.pages.dashboard'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->visible(fn (): bool => filament()->auth()->user()?->can('panelAccess.admin') ?? false)
                    ->sort(999),
                NavigationItem::make('Gate Site')
                    ->url(fn () => route('filament.gate.pages.dashboard'))
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn (): bool => filament()->auth()->user()?->can('panelAccess.gate') ?? false)
                    ->sort(999),
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    // @phpstan-ignore argument.type
                    ->label(fn () => filament()->getUserName(filament()->auth()->user()))
                    ->url(fn () => route('filament.app.profile'))
                    ->icon('heroicon-o-user-circle'),
            ]);
    }

    public function register(): void
    {
        parent::register();
        // Register everything in admin, it shows up in both panels anyway
    }
}
