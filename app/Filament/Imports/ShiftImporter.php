<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ShiftImporter extends Importer
{
    protected static ?string $model = Shift::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('shift_type')
                ->label('Shift Type')
                ->guess(['task', 'shift_type', 'name'])
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->helperText('The name of the shift type')
                ->fillRecordUsing(function (Shift $record, string $state): void {
                    // Ignore this value in the shift itself, it's used to create the shift type
                }),
            ImportColumn::make('description')
                ->label('Description')
                ->ignoreBlankState()
                ->fillRecordUsing(function (Shift $record, string $state): void {
                    // Ignore this value in the shift itself, it's used to create the shift type
                }),
            ImportColumn::make('location')
                ->label('Location')
                ->ignoreBlankState()
                ->fillRecordUsing(function (Shift $record, string $state): void {
                    // Ignore this value in the shift itself, it's used to create the shift type
                }),
            ImportColumn::make('date')
                ->label('Date')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->fillRecordUsing(function (Shift $record, string $state): void {
                    // Ignore this value in the shift itself, it's used to calculate start_offset
                }),
            ImportColumn::make('start_time')
                ->label('Start Time')
                ->requiredMapping()
                ->rules(['date_format:g:i A'])
                ->fillRecordUsing(function (Shift $record, string $state): void {
                    // Ignore this value in the shift itself, it's used to calculate start_offset and length
                }),
            ImportColumn::make('end_time')
                ->label('End Time')
                ->requiredMapping()
                ->rules(['date_format:g:i A'])
                ->fillRecordUsing(function (Shift $record, string $state): void {
                    // Ignore this value in the shift itself, it's used to calculate length
                }),
            ImportColumn::make('num_spots')
                ->label('Number of Spots')
                ->guess(['quantity', 'num_spots', 'number_of_spots', 'spots'])
                ->requiredMapping()
                ->rules(['integer']),
            ImportColumn::make('multiplier')
                ->label('Multiplier')
                ->rules(['numeric', 'between:0,2'])
                ->fillRecordUsing(function (Shift $record, ?float $state): void {
                    // Ignore this value in the shift itself, it's handled in beforeCreate
                }),
        ];
    }

    public function resolveRecord(): ?Shift
    {
        // TODO: Figure out a way to match existing shifts
        // Tricky part is matching shift_type_id
        return new Shift;
    }

    protected function beforeCreate(): void
    {
        $data = $this->getData();
        $teamId = $this->options['teamId'];

        $shiftType = ShiftType::firstOrCreate([
            'title' => $data['shift_type'],
            'team_id' => $teamId,
        ],
            [
                'description' => $data['description'],
                'location' => $data['location'] ?? 'TBD',
                'length' => $this->calculateDuration($data),
                'num_spots' => $data['num_spots'],
            ]);

        /** @var Shift $record */
        $record = $this->record;

        $record->shift_type_id = $shiftType->id;

        // Calculate the start offset based on the event's volunteer base date
        $record->start_offset = $this->calculateOffset($data);

        // Calculate the length of the shift in minutes
        $record->length = $this->calculateDuration($data);

        $record->multiplier = $data['multiplier'] ?? 1.0;

        $this->record = $record;
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function calculateDuration(array $data): int
    {
        $start = Carbon::parse($data['start_time']);
        $end = Carbon::parse($data['end_time']);

        if ($start > $end) {
            // If the end time is before the start time, assume it goes to the next day
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function calculateOffset(array $data): int
    {
        /** @var Event $event */
        $event = Event::where('id', $this->options['eventId'] ?? Event::getCurrentEventId())->firstOrFail();
        $start = Carbon::parse($data['date'] . ' ' . $data['start_time'], 'America/New_York');

        return $event ? (int) $event->volunteerBaseDate->diffInMinutes($start) : 0;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your shift import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
