<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketTypeResource\Pages;

use App\Filament\Admin\Resources\TicketTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTicketType extends ViewRecord
{
    protected static string $resource = TicketTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
