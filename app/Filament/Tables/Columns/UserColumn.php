<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use App\Filament\Concerns\HasConditionalUserDisplay;
use Filament\Tables\Columns\TextColumn;

class UserColumn extends TextColumn
{
    use HasConditionalUserDisplay;
}
