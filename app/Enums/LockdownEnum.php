<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\WebLockdownService;
use Filament\Support\Contracts\HasLabel;

enum LockdownEnum implements HasLabel
{
    case Tickets;
    case Volunteers;
    case Grants;

    public function getLabel(): string
    {
        return match ($this) {
            self::Tickets => 'Tickets Lockdown',
            self::Volunteers => 'Volunteers Lockdown',
            self::Grants => 'Grants Lockdown',
        };
    }

    public function getKey(): string
    {
        return match ($this) {
            self::Tickets => 'ticketsLockdown',
            self::Volunteers => 'volunteersLockdown',
            self::Grants => 'grantsLockdown',
        };
    }

    public function isLocked(bool $global = false): bool
    {
        return app(WebLockdownService::class)->getLockdownStatus($this, $global);
    }
}
