<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BanResource\Pages;

use App\Filament\Admin\Resources\BanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBans extends ListRecords
{
    protected static string $resource = BanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ban User'),
        ];
    }
}
