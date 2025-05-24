<?php

declare(strict_types=1);

namespace App\Filament\NestedResources;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * A child of \Guava\FilamentNestedResources\Ancestor that retrieves
 * the far ancestor of a record for a HasManyThrough relationship.
 */
class FarAncestor extends \Guava\FilamentNestedResources\Ancestor
{
    public function getResource(Model $record): string
    {
        $relationship = $this->getRelationship($record);

        // Calls getFarParent() instead of getParent() to skip the middle model
        $resource = Filament::getModelResource($relationship->getFarParent());

        // Make PHPStan happy
        if ($resource === null) {
            throw new \RuntimeException('Resource not found');
        }

        return $resource;
    }
}
