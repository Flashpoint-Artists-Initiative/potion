<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReservedTicketResource\Pages;

use App\Filament\Admin\Resources\ReservedTicketResource;
use App\Models\Ticketing\ReservedTicket;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewReservedTicket extends ViewRecord
{
    protected static string $resource = ReservedTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getRecordTitle(): string|Htmlable
    {
        /** @var ReservedTicket $record */
        $record = $this->getRecord();

        return sprintf('Reserved Ticket #%s', $record->id);
    }
}
