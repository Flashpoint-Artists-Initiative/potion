<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ApiLockdownService
{
    /**
     * @return string[]
     */
    public static function lockdownTypes(): array
    {
        return ['site', 'ticket', 'volunteer', 'grants'];
    }

    public function setLockdown(string $type, bool $status): void
    {
        abort_unless(in_array($type, static::lockdownTypes()), 422);

        if ($status) {
            Cache::forever($this->keyName($type), $status);

            return;
        }

        Cache::forget($this->keyName($type));
    }

    public function getLockdown(string $type): bool
    {
        abort_unless(in_array($type, static::lockdownTypes()), 422);

        return Cache::get($this->keyName($type), false);
    }

    /**
     * @return array<string, bool>
     */
    public function getLockdownStatus(): array
    {
        $output = [];

        foreach (self::lockdownTypes() as $type) {
            $output[$type] = $this->getLockdown($type);
        }

        return $output;
    }

    protected function keyName(string $type): string
    {
        return 'lockdown.' . $type;
    }
}
