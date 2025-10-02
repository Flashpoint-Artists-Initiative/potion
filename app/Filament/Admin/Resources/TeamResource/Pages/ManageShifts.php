<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Enums\LockdownEnum;
use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Imports\ShiftImporter;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use Filament\Actions\CreateAction as ActionsCreateAction;
use Filament\Actions\ImportAction;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Guava\FilamentNestedResources\Ancestor;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Guava\FilamentNestedResources\Concerns\NestedRelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ManageShifts extends ManageRelatedRecords
{
    use NestedPage; // Since this is a standalone page, we also need this trait
    use NestedRelationManager;

    protected static string $resource = TeamResource::class;

    protected static string $relationship = 'shifts';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected function getHeaderActions(): array
    {
        $recordId = $this->record instanceof Model ? $this->record->getKey() : $this->record;

        return [
            ActionsCreateAction::make()
                ->label('Add Shift')
                ->url(fn () => CreateShift::getUrl([
                    'record' => $this->getOwnerRecord(),
                ]))
                ->icon('heroicon-o-plus')
                ->color('primary'),
            ImportAction::make()
                ->label('Import Shifts')
                ->importer(ShiftImporter::class)
                ->options([
                    'eventId' => Event::getCurrentEventId(),
                    'teamId' => $recordId,
                ])
                ->chunkSize(30)
                ->visible(fn () => Auth::user()?->can('teams.create') && ! LockdownEnum::Volunteers->isLocked()),
        ];
    }

    public function getTabs(): array
    {
        /** @var Team $team */
        $team = $this->getOwnerRecord();

        $tabs = [
            'all' => Tab::make('All'),
        ];

        $tabs += $team->shiftTypes->map(function ($shiftType) {
            return Tab::make($shiftType->title)
                ->query(fn (Builder $query) => $query->where('shift_type_id', $shiftType->id));
        })->toArray();

        return $tabs;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('start_offset', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount('volunteers');
            })
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(['shift_types.title']),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Start')
                    ->dateTime('D n/j, g:i A T', 'America/New_York') // Already in the correct timezone from the event mutator
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
                Tables\Actions\ViewAction::make(),
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
