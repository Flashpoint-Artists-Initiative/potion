<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Models\Event;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['event_id'] = Event::getCurrentEventId();

        return $data;
    }
}
