<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftTypeResource\Pages;

use App\Filament\Admin\Resources\ShiftTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class EditShiftType extends EditRecord
{
    use NestedPage;

    protected static string $resource = ShiftTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
