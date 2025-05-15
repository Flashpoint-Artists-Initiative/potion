<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PurchasedTicketResource\Pages;
use App\Filament\Infolists\Components\UserEntry;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Event;
use App\Models\Ticketing\PurchasedTicket;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class PurchasedTicketResource extends Resource
{
    protected static ?string $model = PurchasedTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Event Specific';

    protected static ?string $navigationParentItem = 'Ticketing';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make([
                    TextEntry::make('ticketType.name'),
                    TextEntry::make('ticketType.created_at')
                        ->label('Purchased Date')
                        ->dateTime('F jS, Y g:i A T', 'America/New_York'),
                    TextEntry::make('order_id'),
                    UserEntry::make('user')
                        ->userPage('tickets'),
                    TextEntry::make('reserved_ticket_id'),
                ]),
                // Forms\Components\Select::make('ticket_type_id')
                //     ->relationship('ticketType', 'name')
                //     ->required(),
                // Forms\Components\Select::make('order_id')
                //     ->relationship('order', 'id'),
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'id')
                //     ->required(),
                // Forms\Components\Select::make('reserved_ticket_id')
                //     ->relationship('reservedTicket', 'id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticketType.name')
                    ->sortable(),
                UserColumn::make('user')
                    ->userPage('tickets'),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order #')
                    ->numeric()
                    ->sortable()
                    ->prefix('#')
                    ->url(fn ($state) => $state ? OrderResource::getUrl('view', ['record' => $state]) : '')
                    ->color('primary')
                    ->icon('heroicon-s-shopping-bag'),
                Tables\Columns\TextColumn::make('reservedTicket.id')
                    ->label('Reserved Ticket #')
                    ->numeric()
                    ->sortable()
                    ->prefix('#')
                    ->url(fn ($state) => $state ? ReservedTicketResource::getUrl('view', ['record' => $state]) : '')
                    ->color('primary')
                    ->icon('heroicon-s-ticket'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListPurchasedTickets::route('/'),
            // 'create' => Pages\CreatePurchasedTicket::route('/create'),
            'view' => Pages\ViewPurchasedTicket::route('/{record}'),
            // 'edit' => Pages\EditPurchasedTicket::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $route = Route::currentRouteName() ?? '';
        $parts = explode('.', $route);
        $lastPart = end($parts);

        if ($lastPart === 'view') {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->whereRelation('ticketType', 'event_id', Event::getCurrentEventId());
    }
}
