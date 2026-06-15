<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource\RelationManagers;

use App\Filament\Tables\Columns\UserColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VolunteersRelationManager extends RelationManager
{
    protected static string $relationship = 'volunteers';

    protected static bool $shouldSkipAuthorization = true;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                UserColumn::makeForUserModel(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->color('primary')
                    ->label('Add Volunteer')
                    ->visible(fn () => Auth::authenticate()->can('shifts.attach')),
            ])
            ->actions([
                DetachAction::make()
                    ->label('Remove')
                    ->visible(fn () => Auth::authenticate()->can('shifts.detach')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
