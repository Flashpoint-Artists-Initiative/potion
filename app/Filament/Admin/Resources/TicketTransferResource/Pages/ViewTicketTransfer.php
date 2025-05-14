<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketTransferResource\Pages;

use App\Filament\Admin\Resources\TicketTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\Ticketing\TicketTransfer;

class ViewTicketTransfer extends ViewRecord
{
    protected static string $resource = TicketTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getRecordTitle(): string|Htmlable
    {
        /** @var TicketTransfer $record */
        $record = $this->getRecord();
        return sprintf('Ticket Transfer #%s', $record->id);
    }
}
