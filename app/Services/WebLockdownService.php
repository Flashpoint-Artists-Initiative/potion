<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LockdownEnum;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

class WebLockdownService
{
    public function enableLockdown(LockdownEnum $type, ?int $eventId = null): void
    {
        $this->setLockdownStatus($type, true, $eventId);
    }

    public function disableLockdown(LockdownEnum $type, ?int $eventId = null): void
    {
        $this->setLockdownStatus($type, false, $eventId);
    }

    protected function setLockdownStatus(LockdownEnum $type, bool $status, ?int $eventId = null): void
    {
        $key = $type->getKey();
        if ($eventId) {
            $event = Event::findOrFail($eventId);
            $event->{$key} = $status;
            $event->save();

            return;
        }

        Cache::forever($key, $status);
    }

    public function getLockdownStatus(LockdownEnum $type, bool $global = false): bool
    {
        // We shouldn't need to check a specific event's status, just the current one
        $event = $global ? null : Event::getCurrentEvent();
        $key = $type->getKey();

        // Event lockdown takes precedence over global lockdown
        if ($event) {
            if ($event->{$key}) {
                return true;
            }
        }

        return Cache::get($key, false);
    }
}
