<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\Ticketing\ReservedTicket;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ReservedTicketImporter extends Importer
{
    protected static ?string $model = ReservedTicket::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->rules(['required', 'email'])
                ->helperText('The email address to send the ticket to'),
            ImportColumn::make('ticket_type')
                ->label('Ticket Type')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->relationship('ticketType', 'name')
                ->helperText('The name of the ticket type as shown in POTION'),
            ImportColumn::make('expiration_date')
                ->label('Expiration Date')
                ->rules(['nullable', 'date'])
                ->helperText("Optional. The date the ticket will expire.  If not set, will default to the ticket type's sale end date."),
            ImportColumn::make('note')
                ->label('Note')
                ->helperText('Optional. A note to be added to the ticket.  Visible to the user.'),
            // Count is not mapped to a database column, but is instead used to create multiple reserved tickets
            // The actual creation of the tickets is done in ReservedTicketObserver
            ImportColumn::make('count')
                ->label('Count')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1'])
                ->helperText('Optional. The number of tickets to reserve.  Defaults to 1.')
        ];
    }

    public function resolveRecord(): ?ReservedTicket
    {
        return new ReservedTicket();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your reserved ticket import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
