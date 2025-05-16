<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PurchasedTicketResource\Pages;

use App\Filament\Admin\Resources\PurchasedTicketResource;
use App\Models\Ticketing\PurchasedTicket;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPurchasedTicket extends ViewRecord
{
    protected static string $resource = PurchasedTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }

    public function getRecordTitle(): string|Htmlable
    {
        /** @var PurchasedTicket $record */
        $record = $this->getRecord();

        return sprintf('Purchased Ticket #%s', $record->id);
    }
}
