<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Admin\Resources\TeamResource\Widgets\ShiftCalendarWidget;
use Filament\Resources\Pages\EditRecord;

class ShiftCalendar extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $navigationLabel = 'Shift Calendar';

    // The view is empty, but keeps the form from being loaded at the bottom of the page
    protected string $view = 'filament.admin.resources.shift-type-resource.pages.shift-calendar';

    protected function getHeaderWidgets(): array
    {
        return [
            ShiftCalendarWidget::class,
        ];
    }
}
