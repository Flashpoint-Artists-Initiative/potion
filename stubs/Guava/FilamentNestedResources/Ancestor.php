<?php

declare(strict_types=1);

namespace Guava\FilamentNestedResources;

use Illuminate\Database\Eloquent\Model;

/**
 * Temporary stub for Filament 5 upgrade — replace with native nested resources.
 */
class Ancestor
{
    public function __construct(
        protected string $relationshipName = '',
        protected string $inverseRelationshipName = '',
    ) {}

    public static function make(string $relationshipName, string $inverseRelationshipName): static
    {
        return new static($relationshipName, $inverseRelationshipName);
    }

    public function getResource(Model $record): string
    {
        return '';
    }

    public function getRelationshipName(): string
    {
        return $this->relationshipName;
    }

    public function getInverseRelationshipName(): ?string
    {
        return $this->inverseRelationshipName;
    }

    public function getRelationship(Model $record): ?FarRelation
    {
        return null;
    }

    public function getRelatedRecord(Model $record): ?Model
    {
        return null;
    }
}
