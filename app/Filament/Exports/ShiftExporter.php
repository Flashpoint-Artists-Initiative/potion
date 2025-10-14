<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Volunteering\Shift;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ShiftExporter extends Exporter
{
    protected static ?string $model = Shift::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('shiftType.title')
                ->label('Position'),
            ExportColumn::make('startCarbon')
                ->label('Start Time')
                ->state(fn (Shift $record) => $record->startCarbon->setTimezone('America/New_York')->format('D, m/j g:ia')),
            ExportColumn::make('endCarbon')
                ->label('End Time')
                ->state(fn (Shift $record) => $record->endCarbon->setTimezone('America/New_York')->format('D, m/j g:ia')),
            ExportColumn::make('volunteers_count')
                ->label('Spots Filled'),
            ExportColumn::make('num_spots')
                ->label('Spots Needed'),
            ExportColumn::make('printableVolunteers')
                ->label('Volunteers')
                ->state(fn (Shift $record) => $record->printableVolunteers->implode("\n")),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        // return number_format($export->successful_rows);
        $body = 'Your volunteer export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    public function getFileDisk(): string
    {
        return 's3';
    }
}
