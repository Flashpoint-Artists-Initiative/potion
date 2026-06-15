<?php

declare(strict_types=1);

namespace Guava\FilamentNestedResources\Concerns;

use Filament\Actions\CreateAction;

/**
 * Temporary stub for Filament 5 upgrade — replace with native nested resources.
 */
trait NestedRelationManager
{
    /**
     * @return class-string
     */
    protected function getNestedResource(): string
    {
        return static::$resource;
    }

    protected function configureCreateAction(CreateAction $action): void {}
}
