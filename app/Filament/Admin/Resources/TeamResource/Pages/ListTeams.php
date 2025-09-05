<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Enums\LockdownEnum;
use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Imports\ShiftImporter;
use App\Models\Event;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Illuminate\Support\Facades\Auth;

class ListTeams extends ListRecords
{
    use NestedPage;

    protected static string $resource = TeamResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->label('Import Teams')
                ->importer(ShiftImporter::class)
                ->options([
                    'eventId' => Event::getCurrentEventId(),
                ])
                ->chunkSize(30)
                ->visible(fn () => Auth::user()?->can('teams.create') && ! LockdownEnum::Volunteers->isLocked()),
        ];
    }
}
