<?php

declare(strict_types=1);

namespace Guava\FilamentNestedResources\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Temporary stub for Filament 5 upgrade — replace with native nested resources.
 */
trait NestedPage
{
    public function getOwnerRecord(): Model
    {
        /** @var Model $record */
        $record = $this->record;

        return $record;
    }
}
