<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketTypeResource\Pages;

use App\Filament\Admin\Resources\TicketTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTicketTypes extends ListRecords
{
    protected static string $resource = TicketTypeResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
