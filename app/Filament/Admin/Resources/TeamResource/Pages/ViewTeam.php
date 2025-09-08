<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Enums\LockdownEnum;
use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Imports\ShiftImporter;
use App\Models\Event;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ViewRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ViewTeam extends ViewRecord
{
    use NestedPage;

    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        $recordId = $this->record instanceof Model ? $this->record->getKey() : $this->record;
        
        return [
            Actions\EditAction::make(),
            // Action::make('Edit Shifts')
            //     ->url(ManageShiftTypes::getUrl(['record' => $this->record])),
            ImportAction::make()
                ->label('Import Shifts')
                ->importer(ShiftImporter::class)
                ->options([
                    'eventId' => Event::getCurrentEventId(),
                    'teamId' => $recordId,
                ])
                ->chunkSize(30)
                ->visible(fn () => Auth::user()?->can('teams.create') && ! LockdownEnum::Volunteers->isLocked()),
        ];
    }
}
