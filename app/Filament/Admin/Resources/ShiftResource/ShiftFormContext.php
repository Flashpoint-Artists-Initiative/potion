<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource;

/**
 * Identifies which admin surface is building the shared shift form.
 *
 * Drives field labels, defaults, and visibility in {@see ShiftForm}.
 */
enum ShiftFormContext
{
    case TeamCreate;
    case ShiftTypeCreate;
    case Calendar;
}
