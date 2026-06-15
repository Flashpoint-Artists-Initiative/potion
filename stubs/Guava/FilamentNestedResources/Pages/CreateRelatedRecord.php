<?php

declare(strict_types=1);

namespace Guava\FilamentNestedResources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

/**
 * Temporary stub for Filament 5 upgrade — replace with native nested resources.
 */
abstract class CreateRelatedRecord extends CreateRecord
{
    public static function getRelationship(): string
    {
        return '';
    }

    protected function makeForm(): Schema
    {
        return $this->form;
    }
}
