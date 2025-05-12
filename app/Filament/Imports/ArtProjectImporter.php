<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\Grants\ArtProject;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ArtProjectImporter extends Importer
{
    protected static ?string $model = ArtProject::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Project Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('user')
                ->label('Artist Email')
                ->relationship(resolveUsing: ['email']),
            ImportColumn::make('description')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('short_description'),
            ImportColumn::make('artist_name')
                ->rules(['max:255']),
            ImportColumn::make('budget_link')
                ->rules(['max:255']),
            ImportColumn::make('min_funding')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('max_funding')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
        ];
    }

    public function resolveRecord(): ?ArtProject
    {
        return ArtProject::firstOrNew([
            // Update existing records, matching them by `name`
            'name' => $this->data['name'],
            'event_id' => $this->options['event_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your art project import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
