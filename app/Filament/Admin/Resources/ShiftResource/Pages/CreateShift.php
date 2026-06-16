<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\ShiftTypeResource;
use App\Models\Volunteering\ShiftType;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    public function form(Schema $schema): Schema
    {
        /** @var ShiftType $shiftType */
        $shiftType = $this->getParentRecord();

        return ShiftResource::form($schema->extraAttributes([
            'shiftType' => $shiftType,
        ]));
    }

    protected function getCancelFormAction(): Action
    {
        /** @var ShiftType $shiftType */
        $shiftType = $this->getParentRecord();

        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->url($this->previousUrl ?? ShiftTypeResource::getUrl('shifts', [
                'record' => $shiftType,
                'team' => $shiftType->team_id,
            ], shouldGuessMissingParameters: true))
            ->color('gray');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var ShiftType $shiftType */
        $shiftType = $this->getParentRecord();

        // prepend shift_type_id here because we have to set the shift_type_id before setting the start_datetime
        // because start_datetime uses the team relationship
        return ['shift_type_id' => $shiftType->id] + $data;
    }
}
