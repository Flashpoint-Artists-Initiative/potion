<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Admin\Resources\TeamResource\Widgets\ShiftCalendarWidget;
use Filament\Resources\Pages\EditRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class ShiftCalendar extends EditRecord
{
    use NestedPage;

    protected static string $resource = TeamResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $navigationLabel = 'Manage Shifts';

    protected static string $view = 'filament.admin.resources.shift-type-resource.pages.shift-calendar';

    protected function getHeaderWidgets(): array
    {
        return [
            ShiftCalendarWidget::class,
        ];
    }
}
