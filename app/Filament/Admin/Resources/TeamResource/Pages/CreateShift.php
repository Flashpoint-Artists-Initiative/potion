<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
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
    protected static string $resource = TeamResource::class;

    /**
     * This is a hacky way to get the owner record to the resource form() method.
     */
    protected function makeForm(): Form
    {
        return parent::makeForm()
            ->extraAttributes([
                'team' => $this->getOwnerRecord(),
            ]);
    }
}
