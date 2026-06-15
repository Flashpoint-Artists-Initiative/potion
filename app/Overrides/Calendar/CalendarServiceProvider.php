<?php

declare(strict_types=1);

namespace App\Overrides\Calendar;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Guava\Calendar\CalendarServiceProvider as OriginalServiceProvider;

/**
 * Override the guava/calendar CalendarServiceProvider to load assets from local resources
 * instead of an external CDN. So everything still works when running locally without
 * internet access.
 */
class CalendarServiceProvider extends OriginalServiceProvider
{
    protected function getPackageBaseDir(): string
    {
        return __DIR__ . '/../../../vendor/guava/calendar/src';
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                AlpineComponent::make(
                    'calendar',
                    __DIR__ . '/../../../vendor/guava/calendar/dist/js/calendar.js',
                ),
                AlpineComponent::make(
                    'calendar-context-menu',
                    __DIR__ . '/../../../vendor/guava/calendar/dist/js/calendar-context-menu.js',
                ),
                AlpineComponent::make(
                    'calendar-event',
                    __DIR__ . '/../../../vendor/guava/calendar/dist/js/calendar-event.js',
                ),
                Css::make('calendar-styles', resource_path('vendor/guava/calendar/event-calendar.min.css')),
                Js::make('calendar-script', resource_path('vendor/guava/calendar/event-calendar.min.js')),
            ],
            package: 'guava/calendar'
        );
    }
}
