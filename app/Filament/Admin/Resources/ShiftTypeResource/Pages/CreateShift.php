<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftTypeResource\Pages;

use App\Filament\Admin\Resources\ShiftTypeResource;
use App\Models\Volunteering\ShiftType;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Guava\FilamentNestedResources\Pages\CreateRelatedRecord;

class CreateShift extends CreateRelatedRecord
{
    use NestedPage;

    // This page also needs to know the ancestor relationship used (just like relation managers):
    protected static string $relationship = 'shifts';

    // We can usually guess the nested resource, but if your app has multiple resources for this
    // model, you will need to explicitly define it
    // public static string $nestedResource = ShiftResource::class;
    protected static string $resource = ShiftTypeResource::class;

    /**
     * This is a hacky way to get the owner record to the resource form() method.
     */
    protected function makeForm(): Form
    {
        return parent::makeForm()
            ->extraAttributes([
                'shiftType' => $this->getOwnerRecord(),
            ]);
    }

    /**
     * Override the cancel action since we don't have a ShiftType index page
     * Sets the url to the ShiftType shifts page
     */
    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->url($this->previousUrl ?? static::getResource()::getUrl('shifts', ['record' => $this->getOwnerRecord()]))
            ->color('gray');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var ShiftType $shiftType */
        $shiftType = $this->getOwnerRecord();

        // prepend shift_type_id here because we have to set the shift_type_id before setting the start_datetime
        // because start_datetime uses the team relationship
        $data = ['shift_type_id' => $shiftType->id] + $data;

        return $data;
    }
}
