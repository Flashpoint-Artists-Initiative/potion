<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LockdownEnum;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

class WebLockdownService
{
    // I don't think we'll ever need to have multiple lockdowns at once
    // but if we do, we can change this to false.  In the meantime,
    // the individual lockdowns can still exist, but they won't be used.
    protected bool $singleLockdown;

    public const GLOBAL_KEY = 'globalLockdown';

    public const GLOBAL_TEXT_KEY = 'globalLockdownText';

    public function __construct()
    {
        $this->singleLockdown = config('app.use_single_lockdown');
    }

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
        if ($this->singleLockdown) {
            Cache::forever(self::GLOBAL_KEY, $status);

            return;
        }

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
        if ($this->singleLockdown) {
            return Cache::get(self::GLOBAL_KEY, false);
        }

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
