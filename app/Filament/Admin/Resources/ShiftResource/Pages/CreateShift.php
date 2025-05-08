<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;
}
