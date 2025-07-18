<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Agencetwogether\HooksHelper\HooksHelperPlugin;
use App\Filament\AvatarProviders\DiceBearProvider;
use CodeWithDennis\FilamentThemeInspector\FilamentThemeInspectorPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use EightCedars\FilamentInactivityGuard\FilamentInactivityGuardPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mchev\Banhammer\Middleware\LogoutBanned;

class CommonPanelProvider extends PanelProvider
{
    public string $id;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id($this->id)
            // ->spa()
            // ->spaUrlExceptions([
            //     '*/admin',
            //     '*/admin/*',
            // ])
            ->brandLogo(fn () => asset('images/logo-text.svg'))
            ->favicon(fn () => asset('images/logo.svg'))
            ->brandLogoHeight('revert-layer')
            ->defaultAvatarProvider(DiceBearProvider::class)
            ->authGuard('web')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LogoutBanned::class,
            ])
            ->discoverResources(...$this->discoverHelper('Resources'))
            ->discoverPages(...$this->discoverHelper('Pages'))
            ->discoverWidgets(...$this->discoverHelper('Widgets'))
            ->discoverClusters(...$this->discoverHelper('Clusters'))
            ->plugins([
                // FilamentInactivityGuardPlugin::make()
                //     ->enabled(! app()->isLocal()),
            ]);

        return $this->addDevPlugins($panel);
    }

    /**
     * @return array<string, string>
     */
    public function discoverHelper(string $resource): array
    {
        $uc = ucfirst($this->id);

        return [
            'in' => app_path("Filament/{$uc}/{$resource}"),
            'for' => "App\\Filament\\{$uc}\\{$resource}",
        ];
    }

    public function register(): void
    {
        parent::register();
        // Don't register hooks here or they will show up twice
    }

    public function addDevPlugins(Panel $panel): Panel
    {
        $plugins = [];
        if (class_exists("Agencetwogether\HooksHelper\HooksHelperPlugin")) {
            $plugins[] = HooksHelperPlugin::make();
        }

        if (class_exists("CodeWithDennis\FilamentThemeInspector\FilamentThemeInspectorPlugin")) {
            // $plugins[] = FilamentThemeInspectorPlugin::make();
        }

        if (class_exists("DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin")) {
            $plugins[] = FilamentDeveloperLoginsPlugin::make()
                ->enabled(app()->environment('local'))
                ->users([
                    'Admin' => 'admin@example.com',
                    'Regular User' => 'regular@example.com',
                    'Unverified User' => 'unverified@example.com',
                    'Event Manager' => 'eventmanager@example.com',
                    'Box Office' => 'boxoffice@example.com',
                    'Art Grant Reviewer' => 'artgrants@example.com',
                ]);
        }

        return $panel->plugins($plugins);
    }
}
