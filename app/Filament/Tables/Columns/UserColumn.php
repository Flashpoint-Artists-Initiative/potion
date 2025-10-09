<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use App\Filament\Concerns\HasConditionalUserDisplay;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class UserColumn extends TextColumn
{
    use HasConditionalUserDisplay;

    protected function additionalSetUp(): void
    {
        $this->searchable(
            query: function (Builder $query, string $search) {
                return $query->whereHas($this->getUserRelation(), function (Builder $query) use ($search) {
                    $query->where('display_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->sortable();
    }
}
