<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArtProjectResource\Pages;

use App\Filament\Admin\Resources\ArtProjectResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

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
                // ->url(route('filament.admin.art-projects.export'))
                ->url(BulkAdjustArtProjects::getUrl())
                ->color('info'),
        ];
    }
}
