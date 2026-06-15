<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReservedTicketResource\Pages;

use App\Filament\Admin\Resources\ReservedTicketResource;
use App\Models\Ticketing\ReservedTicket;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditReservedTicket extends EditRecord
{
    protected static string $resource = ReservedTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getRecordTitle(): string|Htmlable
    {
        /** @var ReservedTicket $record */
        $record = $this->getRecord();

        return sprintf('Reserved Ticket #%s', $record->id);
    }
}
