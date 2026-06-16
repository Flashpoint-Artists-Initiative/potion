<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShiftTypeResource\Pages;
use App\Models\Volunteering\ShiftType;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class ShiftTypeResource extends Resource
{
    protected static ?string $model = ShiftType::class;

    protected static ?string $parentResource = TeamResource::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->label('Check-in Location')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('length')
                    ->label('Default Length (hours)')
                    ->numeric()
                    ->minValue(.25)
                    ->step(.25)
                    ->default(120)
                    ->formatStateUsing(fn ($state) => $state / 60)
                    ->dehydrateStateUsing(fn ($state) => $state * 60)
                    ->required(),
                Forms\Components\TextInput::make('num_spots')
                    ->label('Default Number of People')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
                Fieldset::make('Shift Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('shade_provided.state')
                                    ->label('Shade Provided')
                                    ->options([
                                        false => 'No',
                                        true => 'Yes',
                                        'note' => 'Other - see details',
                                    ])
                                    ->selectablePlaceholder(false)
                                    ->default(false)
                                    ->live(),
                                Forms\Components\TextInput::make('shade_provided.note')
                                    ->label('Shade Provided Details')
                                    ->helperText('This will show up as a tooltip.')
                                    ->hidden(fn (Get $get) => $get('shade_provided.state') !== 'note')
                                    ->required(fn (Get $get) => $get('shade_provided.state') === 'note'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('long_standing.state')
                                    ->label('Long Periods of Standing')
                                    ->options([
                                        false => 'No',
                                        true => 'Yes',
                                        'note' => 'Other - see details',
                                    ])
                                    ->selectablePlaceholder(false)
                                    ->default(false)
                                    ->live(),
                                Forms\Components\TextInput::make('long_standing.note')
                                    ->label('Long Periods of Standing Details')
                                    ->helperText('This will show up as a tooltip.')
                                    ->hidden(fn (Get $get) => $get('long_standing.state') !== 'note')
                                    ->required(fn (Get $get) => $get('long_standing.state') === 'note'),
                            ]),
                        Forms\Components\TextInput::make('physical_requirements.note')
                            ->label('Physical Requirements Details')
                            ->helperText('Enter any physical requirements for this shift. This will show up as a tooltip.')
                            ->columnSpanFull(),
                    ])
                    ->statePath('details'),
            ]);
    }

    // public static function table(Table $table): Table
    // {
    //     return $table
    //         ->columns([
    //             Tables\Columns\TextColumn::make('created_at')
    //                 ->dateTime()
    //                 ->sortable()
    //                 ->toggleable(isToggledHiddenByDefault: true),
    //             Tables\Columns\TextColumn::make('updated_at')
    //                 ->dateTime()
    //                 ->sortable()
    //                 ->toggleable(isToggledHiddenByDefault: true),
    //             Tables\Columns\TextColumn::make('deleted_at')
    //                 ->dateTime()
    //                 ->sortable()
    //                 ->toggleable(isToggledHiddenByDefault: true),
    //             Tables\Columns\TextColumn::make('team.name')
    //                 ->numeric()
    //                 ->sortable(),
    //             Tables\Columns\TextColumn::make('title')
    //                 ->searchable(),
    //             Tables\Columns\TextColumn::make('length')
    //                 ->numeric()
    //                 ->sortable(),
    //             Tables\Columns\TextColumn::make('num_spots')
    //                 ->numeric()
    //                 ->sortable(),
    //         ])
    //         ->filters([
    //             //
    //         ])
    //         ->actions([
    //             \Filament\Actions\EditAction::make(),
    //         ])
    //         ->bulkActions([
    //             \Filament\Actions\BulkActionGroup::make([
    //                 \Filament\Actions\DeleteBulkAction::make(),
    //             ]),
    //         ]);
    // }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => Pages\CreateShiftType::route('/create'),
            'edit' => Pages\EditShiftType::route('/{record}/edit'),
            'view' => Pages\ViewShiftType::route('/{record}'),

            'shifts' => Pages\ManageShifts::route('/{record}/shifts'),
        ];
    }

    public static function getBreadcrumbRecordLabel(ShiftType $record): string
    {
        return $record->title;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewShiftType::class,
            Pages\EditShiftType::class,
            Pages\ManageShifts::class,
        ]);
    }
}
