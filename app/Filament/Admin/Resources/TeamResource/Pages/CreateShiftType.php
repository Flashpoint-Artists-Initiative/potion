<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Guava\FilamentNestedResources\Pages\CreateRelatedRecord;

class CreateShiftType extends CreateRelatedRecord
{
    use NestedPage;

    // This page also needs to know the ancestor relationship used (just like relation managers):
    protected static string $relationship = 'shiftTypes';

    // We can usually guess the nested resource, but if your app has multiple resources for this
    // model, you will need to explicitly define it
    // public static string $nestedResource = AlbumResource::class;
    protected static string $resource = TeamResource::class;
}
