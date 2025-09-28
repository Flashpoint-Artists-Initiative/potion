<?php

declare(strict_types=1);

namespace App\Filament\AvatarProviders;

use App\Models\User;
use Filament\AvatarProviders\Contracts;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class OfflineProvider implements Contracts\AvatarProvider
{
    /** @param User $record */
    public function get(Model|Authenticatable $record): string
    {
        return asset('/images/alchemy_logo.svg');
    }
}
