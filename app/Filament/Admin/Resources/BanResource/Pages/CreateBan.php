<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BanResource\Pages;

use App\Filament\Admin\Resources\BanResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBan extends CreateRecord
{
    protected static string $resource = BanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::where('email', $data['email'])->firstOrFail();
        unset($data['email']);

        return $user->ban($data);
    }
}
