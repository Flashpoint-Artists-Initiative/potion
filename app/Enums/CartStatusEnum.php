<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CartStatusEnum: string implements HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Completed => 'Completed',
        };
    }
}

//
