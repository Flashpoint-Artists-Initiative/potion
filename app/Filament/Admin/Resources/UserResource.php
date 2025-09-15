<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Actions\SendEmailBulkAction;
use App\Enums\RolesEnum;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Table;
use Gerenuk\FilamentBanhammer\Resources\Actions\BanAction;
use Gerenuk\FilamentBanhammer\Resources\Actions\UnbanAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make([
                    Infolists\Components\TextEntry::make('legal_name')
                        ->label('Legal Name')
                        ->visible(fn () => Auth::authenticate()->can('users.viewPrivate')),
                    Infolists\Components\TextEntry::make('display_name')
                        ->label('Display Name'),
                    Infolists\Components\TextEntry::make('email'),
                    Infolists\Components\TextEntry::make('birthday')
                        ->visible(fn () => Auth::authenticate()->can('users.viewPrivate')),
                ])->columns(2),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('legal_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('preferred_name')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birthday')
                    ->closeOnDateSelection(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->timezone('America/New_York'),
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
                Tables\Columns\TextColumn::make('legal_name')
                    ->searchable()
                    ->visible(fn () => Auth::authenticate()->can('users.viewPrivate'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('display_name')
                    ->label(fn () => Auth::authenticate()->can('users.viewPrivate') ? 'Display Name' : 'Name')
                    ->searchable(['preferred_name'])
                    ->sortable(),
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
                QueryBuilder::make()
                    ->constraints([
                        RelationshipConstraint::make('orders')
                            ->icon('heroicon-o-shopping-bag')
                            ->multiple(),
                        RelationshipConstraint::make('purchasedTickets')
                            ->icon('heroicon-o-ticket')
                            ->multiple(),
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    BanAction::make()
                        ->hidden(fn ($record) => $record->isBanned()),
                    UnbanAction::make()
                        ->hidden(fn ($record) => ! $record->isBanned()),
                ])
                    ->visible(fn () => Auth::authenticate()->can('users.ban')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    SendEmailBulkAction::make()
                        ->hidden(fn () => ! Auth::authenticate()->hasRole(RolesEnum::Admin)),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
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
            'waivers' => Pages\UserWaivers::route('/{record}/waivers'),
            'carts' => Pages\UserCarts::route('/{record}/carts'),
            'transfers' => Pages\UserTransfers::route('/{record}/transfers'),
            'tickets' => Pages\UserPurchasedTickets::route('/{record}/tickets'),
            'reserved' => Pages\UserReservedTickets::route('/{record}/reserved'),
            'shifts' => Pages\UserVolunteerShifts::route('/{record}/shifts'),
            'stripe' => Pages\UserStripeData::route('/{record}/stripe'),
            'audits' => Pages\UserAudits::route('/{record}/audits'),
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
            Pages\UserWaivers::class,
            Pages\UserOrders::class,
            Pages\UserCarts::class,
            Pages\UserTransfers::class,
            Pages\UserPurchasedTickets::class,
            Pages\UserReservedTickets::class,
            Pages\UserVolunteerShifts::class,
            Pages\UserStripeData::class,
            Pages\UserAudits::class,
        ]);
    }
}
