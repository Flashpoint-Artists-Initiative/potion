<?php

declare(strict_types=1);

namespace App\Filament\Infolists\Components;

use App\Filament\Concerns\HasConditionalUserDisplay;
use Filament\Infolists\Components\TextEntry;

class UserEntry extends TextEntry
{
    use HasConditionalUserDisplay;
}
