<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftTypeResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\ShiftTypeResource;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use Filament\Actions\CreateAction as ActionsCreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Guava\FilamentNestedResources\Ancestor;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Guava\FilamentNestedResources\Concerns\NestedRelationManager;
use Illuminate\Database\Eloquent\Builder;

class ManageShifts extends ManageRelatedRecords
{
    use NestedPage; // Since this is a standalone page, we also need this trait
    use NestedRelationManager;

    protected static string $resource = ShiftTypeResource::class;

    protected static string $relationship = 'shifts';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected function getHeaderActions(): array
    {
        return [
            ActionsCreateAction::make()
                ->label('Add Shift')
                ->url(fn () => CreateShift::getUrl([
                    'record' => $this->getOwnerRecord(),
                ]))
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount('volunteers');
            })
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(['shift_types.title']),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Start')
                    ->dateTime('D n/j, g:i A') // Already in the correct timezone from the event mutator
                    ->sortable(['start_offset']),
                Tables\Columns\TextColumn::make('length_in_hours')
                    ->label('Length')
                    ->sortable(['length'])
                    ->width(1)
                    ->numeric()
                    ->suffix('h'),
                Tables\Columns\TextColumn::make('volunteers_count')
                    ->label('Spots Filled')
                    ->formatStateUsing(fn (Shift $record, $state) => sprintf('%d/%d (%.1f%%)', $record->volunteers_count, $record->num_spots, $record->percentFilled))
                    ->sortable(),
            ])
            ->recordUrl(
                fn (Shift $record): string => ShiftResource::getUrl('view', ['record' => $record])
            )
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Overloaded from NestedCreateAction
     * Uses the relationship getFarParent() instead of getParent() because
     * Team -> Shift is a HasManyThrough relationship
     */
    protected function configureCreateAction(CreateAction $action): void
    {
        $resource = $this->getNestedResource();

        /** @var Ancestor $ancestor */
        $ancestor = $resource::getAncestor();

        $relationship = $ancestor->getRelationship($this->getOwnerRecord());

        // This is the only line that's been changed
        /** @var class-string $ancestorResource */
        $ancestorResource = Filament::getModelResource($relationship->getFarParent());

        if (! $ancestorResource::hasPage("{$ancestor->getRelationshipName()}.create")) {
            throw new \Exception("{$ancestorResource} does not have a nested create page. Please make sure to create it and that it is called '{$ancestor->getRelationshipName()}.create'. Check the documentation for more information.");
        }

        parent::configureCreateAction($action->url(
            fn () => $ancestorResource::getUrl("{$ancestor->getRelationshipName()}.create", [
                'record' => $this->getOwnerRecord(),
            ])
        ));
    }
}
