<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArtProjectResource\Pages;

use App\Enums\LockdownEnum;
use App\Filament\Admin\Resources\ArtProjectResource;
use App\Filament\Imports\ArtProjectImporter;
use App\Models\Event;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListArtProjects extends ListRecords
{
    protected static string $resource = ArtProjectResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('bulk-adjustments')
                ->label('Bulk Adjustments')
                ->icon('heroicon-o-adjustments-vertical')
                ->url(BulkAdjustArtProjects::getUrl())
                ->color('info')
                ->visible(fn () => Auth::user()?->can('artProjects.update') && ! LockdownEnum::Grants->isLocked()),
            ImportAction::make()
                ->label('Import')
                ->importer(ArtProjectImporter::class)
                ->options([
                    'event_id' => Event::getCurrentEventId(),
                ])
                ->chunkSize(20) // This is super small, but art projects tend to have a lot of data
                ->visible(fn () => Auth::user()?->can('artProjects.create') && ! LockdownEnum::Tickets->isLocked()),
        ];
    }
}
