<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource;

enum ShiftFormContext
{
    case TeamCreate;
    case ShiftTypeCreate;
    case Calendar;
}
