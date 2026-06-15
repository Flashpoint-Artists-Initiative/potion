<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use Filament\Pages\Page;

class Stats extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.gate.pages.stats';

    protected static ?int $navigationSort = 4;
}
