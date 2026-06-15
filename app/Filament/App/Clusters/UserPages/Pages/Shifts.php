<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Filament\App\Clusters\UserPages;
use App\Filament\App\Widgets\UserShifts;
use Filament\Pages\Page;

class Shifts extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Volunteer Shifts';

    protected string $view = 'filament.app.clusters.user-pages.pages.shifts';

    protected static ?string $cluster = UserPages::class;

    protected function getHeaderWidgets(): array
    {
        return [
            UserShifts::class,
        ];
    }
}
