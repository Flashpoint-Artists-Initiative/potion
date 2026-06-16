<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftTypeResource\Pages;

use App\Filament\Admin\Resources\ShiftTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShiftType extends CreateRecord
{
    protected static string $resource = ShiftTypeResource::class;
}
