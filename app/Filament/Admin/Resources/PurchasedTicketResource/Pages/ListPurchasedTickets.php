<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PurchasedTicketResource\Pages;

use App\Filament\Admin\Resources\PurchasedTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchasedTickets extends ListRecords
{
    protected static string $resource = PurchasedTicketResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];
    
    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
