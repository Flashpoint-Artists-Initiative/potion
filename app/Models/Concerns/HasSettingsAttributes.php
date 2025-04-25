<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasSettingsAttributes
{
    /**
     * Create the getter and setter for a settings attribute.
     * Returns an array to avoid laravel thinking this function itself is an attribute
     *
     * @return callable[]
     */
    protected function createSettingsAttributeFunctions(string $key, mixed $defaultValue, string $attribute = 'settings'): array
    {
        return [
            // Get
            fn (mixed $value, array $attributes) => $this->{$attribute}[$key] ?? $defaultValue,
            // Set
            function (float $value) use ($key, $attribute) {
                $this->{$attribute}[$key] = $value;

                return [];
            },
        ];
    }
}
