<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

trait HasSettingsAttributes
{
    /**
     * Create the getter and setter for a settings attribute.
     * Returns an array to avoid laravel thinking this function itself is an attribute
     *
     * @return callable[]
     */
    protected function createSettingsAttributeFunctions(string $key, mixed $defaultValue, ?string $type = null, string $attribute = 'settings'): array
    {
        return [
            // Get
            function (mixed $value, array $attributes) use ($key, $defaultValue, $attribute): mixed {
                return Arr::dot($this->{$attribute}->toArray())[$key] ?? $defaultValue;
            },
            // Set
            function (mixed $value) use ($key, $type, $attribute) {
                match ($type) {
                    'int' => $value = (int) $value,
                    'float' => $value = (float) $value,
                    'bool' => $value = (bool) $value,
                    'string' => $value = (string) $value,
                    'array' => $value = (array) $value,
                    default => $value,
                };

                $dotArray = Arr::dot($this->{$attribute}->toArray());
                $dotArray[$key] = $value;
                $this->{$attribute} = Arr::undot($dotArray);

                return [];
            },
        ];
    }
}
