<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use Filament\Pages\Page;

class Stats extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.gate.pages.stats';
}
