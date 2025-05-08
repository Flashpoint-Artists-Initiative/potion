<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftTypeResource\Pages;

use App\Filament\Admin\Resources\ShiftTypeResource;
use Filament\Resources\Pages\ViewRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;

class ViewShiftType extends ViewRecord
{
    use NestedPage;

    protected static string $resource = ShiftTypeResource::class;
}
