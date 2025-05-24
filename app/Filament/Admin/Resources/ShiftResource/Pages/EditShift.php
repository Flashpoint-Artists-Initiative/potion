<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Models\Volunteering\Shift;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class EditShift extends EditRecord
{
    use NestedPage;

    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getSubNavigationParameters(): array
    {
        /** @var Shift $record */
        $record = $this->getRecord();

        return [
            'record' => $record->team,
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Shift';
    }
}
