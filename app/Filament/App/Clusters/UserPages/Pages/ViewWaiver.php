<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Filament\App\Clusters\UserPages;
use Filament\Pages\Page;

class ViewWaiver extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.app.clusters.user-pages.pages.view-waiver';

    protected static ?string $cluster = UserPages::class;
}
