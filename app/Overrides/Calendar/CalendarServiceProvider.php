<?php

declare(strict_types=1);

namespace App\Overrides\Calendar;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Guava\Calendar\CalendarServiceProvider as OriginalServiceProvider;
use Guava\Calendar\Widgets\CalendarWidget;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Livewire::component('calendar-widget', CalendarWidget::class);

        FilamentAsset::register(
            assets: [
                AlpineComponent::make(
                    'calendar-widget',
                    __DIR__ . '/../../../vendor/guava/calendar/dist/js/calendar-widget.js',
                ),
                AlpineComponent::make(
                    'calendar-context-menu',
                    __DIR__ . '/../../../vendor/guava/calendar/dist/js/calendar-context-menu.js',
                ),
                Css::make('calendar-styles', resource_path('css/guava/calendar/event-calendar.min.css')),
                Js::make('calendar-script', resource_path('js/guava/calendar/event-calendar.min.js')),
            ],
            package: 'guava/calendar'
        );
    }
}
