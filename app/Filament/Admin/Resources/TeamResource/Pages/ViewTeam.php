<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class ViewTeam extends ViewRecord
{
    use NestedPage;

    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            // Action::make('Edit Shifts')
            //     ->url(ManageShiftTypes::getUrl(['record' => $this->record])),
        ];
    }
}
