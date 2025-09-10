<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Models\User;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Guava\FilamentNestedResources\Concerns\NestedRelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ViewVolunteers extends ManageRelatedRecords
{
    use NestedPage; // Since this is a standalone page, we also need this trait
    use NestedRelationManager;

    protected static string $resource = TeamResource::class;

    protected static string $relationship = 'volunteers';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static string $layout = 'layouts.custom'; // Use the simple layout

    public static function getNavigationLabel(): string
    {
        return 'Volunteers';
    }

    public function getHeaderActions(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Team $team */
        $team = $this->record;

        return $table
            ->query($team->shifts()->getQuery())
            ->defaultSort('start_offset', 'asc')
            ->columns([
                TextColumn::make('shiftType.title')
                    ->label('Position')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('startCarbon')
                    ->label('Start Time')
                    ->dateTime('D, m/j g:ia', 'America/New_York')
                    ->sortable(),
                TextColumn::make('endCarbon')
                    ->label('End Time')
                    ->dateTime('D, m/j g:ia', 'America/New_York')
                    ->sortable(),
                TextColumn::make('num_spots')
                    ->label('Filled')
                    ->formatStateUsing(fn(Shift $record) => sprintf('%d/%d', $record->volunteers_count, $record->num_spots))
                    ->counts('volunteers')
                    ->sortable(),
                TextColumn::make('printableVolunteers')
                    ->label('Volunteers')
                    ->listWithLineBreaks()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                ExportAction::make()
                    ->label('Export Volunteers')
                    ->color('primary')
                    ->exporter(\App\Filament\Exports\ShiftExporter::class)
                    ->fileName(fn(): string => str($team->name)->slug() . '-shifts-' . now()->format('Y-m-d')),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
