<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Models\Volunteering\Shift;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class ViewShift extends ViewRecord
{
    use NestedPage;

    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
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
