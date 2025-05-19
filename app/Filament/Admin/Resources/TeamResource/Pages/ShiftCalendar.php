<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Admin\Resources\TeamResource\Widgets\ShiftCalendarWidget;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class ShiftCalendar extends EditRecord
{
    use NestedPage;

    protected static string $resource = TeamResource::class;

    protected static string $view = 'filament.admin.resources.shift-type-resource.pages.shift-calendar';

    protected function getHeaderWidgets(): array
    {
        return [
            ShiftCalendarWidget::class,
        ];
    }
}
