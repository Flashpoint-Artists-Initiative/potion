<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CartResource\Pages;

use App\Filament\Admin\Resources\CartResource;
use Filament\Resources\Pages\ListRecords;

class ListCarts extends ListRecords
{
    protected static string $resource = CartResource::class;
}
