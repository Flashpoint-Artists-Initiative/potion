<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShiftTypeResource\Pages;
use App\Models\Volunteering\ShiftType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentNestedResources\Ancestor;
use Guava\FilamentNestedResources\Concerns\NestedResource;

class ShiftTypeResource extends Resource
{
    use NestedResource;

    protected static ?string $model = ShiftType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->dehydrateStateUsing(fn ($state) => $state * 60),
                Forms\Components\TextInput::make('num_spots')
                    ->label('Default Number of People')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
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
    //             Tables\Actions\EditAction::make(),
    //         ])
    //         ->bulkActions([
    //             Tables\Actions\BulkActionGroup::make([
    //                 Tables\Actions\DeleteBulkAction::make(),
    //             ]),
    //         ]);
    // }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditShiftType::route('/{record}/edit'),
            'view' => Pages\ViewShiftType::route('/{record}'),

            'shifts' => Pages\ManageShifts::route('/{record}/shifts'),
            'shifts.create' => Pages\CreateShift::route('/{record}/shifts/create'),
        ];
    }

    public static function getAncestor(): ?Ancestor
    {
        return Ancestor::make('shiftTypes', 'team');
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
