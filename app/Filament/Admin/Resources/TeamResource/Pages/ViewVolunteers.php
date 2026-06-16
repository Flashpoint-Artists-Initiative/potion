<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use App\Filament\Exports\ShiftExporter;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use Filament\Actions\ExportAction;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ViewVolunteers extends ManageRelatedRecords
{
    protected static string $resource = TeamResource::class;

    protected static string $relationship = 'volunteers';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static string $layout = 'layouts.custom'; // Use the simple layout

    public static function canAccess(array $parameters = []): bool
    {
        return Auth::user() && Auth::user()->can('teams.volunteers');
    }

    public static function getNavigationLabel(): string
    {
        return 'Volunteers';
    }

    public function getHeaderActions(): array
    {
        return [];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('start_offset', $direction)),
                TextColumn::make('endCarbon')
                    ->label('End Time')
                    ->dateTime('D, m/j g:ia', 'America/New_York')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw('(start_offset + CAST(`shifts`.length AS SIGNED)) ' . $direction)),
                TextColumn::make('num_spots')
                    ->label('Filled')
                    ->formatStateUsing(fn (Shift $record) => sprintf('%d/%d', $record->volunteers_count, $record->num_spots))
                    ->counts('volunteers')
                    ->sortable(),
                TextColumn::make('printableVolunteers')
                    ->label('Volunteers')
                    ->listWithLineBreaks(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // \Filament\Actions\CreateAction::make(),
                ExportAction::make()
                    ->label('Export Volunteers')
                    ->color('primary')
                    ->exporter(ShiftExporter::class)
                    ->fileName(fn (): string => str($team->name)->slug() . '-shifts-' . now()->format('Y-m-d')),
            ])
            ->actions([
                // \Filament\Actions\EditAction::make(),
                // \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // \Filament\Actions\BulkActionGroup::make([
                //     \Filament\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
