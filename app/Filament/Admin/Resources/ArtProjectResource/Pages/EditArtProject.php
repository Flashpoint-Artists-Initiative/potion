<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArtProjectResource\Pages;

use App\Filament\Admin\Resources\ArtProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditArtProject extends EditRecord
{
    protected static string $resource = ArtProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
