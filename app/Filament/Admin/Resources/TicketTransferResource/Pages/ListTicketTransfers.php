<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketTransferResource\Pages;

use App\Filament\Admin\Resources\TicketTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTicketTransfers extends ListRecords
{
    protected static string $resource = TicketTransferResource::class;
    
    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];
}
