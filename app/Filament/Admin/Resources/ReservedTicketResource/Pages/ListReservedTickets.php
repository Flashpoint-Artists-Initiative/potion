<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReservedTicketResource\Pages;

use App\Enums\LockdownEnum;
use App\Filament\Admin\Resources\ReservedTicketResource;
use App\Filament\Imports\ReservedTicketImporter;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListReservedTickets extends ListRecords
{
    protected static string $resource = ReservedTicketResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(ReservedTicketImporter::class)
                ->visible(fn () => Auth::user()?->can('reservedTickets.create') && ! LockdownEnum::Tickets->isLocked()),
        ];
    }
}
