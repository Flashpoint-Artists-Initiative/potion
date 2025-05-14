<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\RolesEnum;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use Filament\Resources\Pages\Page;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('legal_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('preferred_name')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birthday'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                // Forms\Components\TextInput::make('password')
                //     ->password()
                //     ->maxLength(255),
                Forms\Components\Select::make('role_id')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('legal_name')
                //     ->searchable()
                //     ->hidden(),
                // Tables\Columns\TextColumn::make('preferred_name')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable(['legal_name', 'preferred_name']),
                Tables\Columns\TextColumn::make('birthday')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('roles', 'name')
                    ->options(RolesEnum::class)
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),

            'orders' => Pages\UserOrders::route('/{record}/orders'),
            'transfers' => Pages\UserTransfers::route('/{record}/transfers'),
            'tickets' => Pages\UserPurchasedTickets::route('/{record}/tickets'),
            'reserved' => Pages\UserReservedTickets::route('/{record}/reserved'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewUser::class,
            Pages\EditUser::class,
            Pages\UserOrders::class,
            Pages\UserTransfers::class,
            Pages\UserPurchasedTickets::class,
            Pages\UserReservedTickets::class,
        ]);
    }
}
