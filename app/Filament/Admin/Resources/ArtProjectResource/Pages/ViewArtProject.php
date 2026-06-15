<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArtProjectResource\Pages;

use App\Filament\Admin\Resources\ArtProjectResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewArtProject extends ViewRecord
{
    protected static string $resource = ArtProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
