<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketTransferResource\Pages;
use App\Filament\Admin\Resources\TicketTransferResource\RelationManagers;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Event;
use App\Models\Ticketing\TicketTransfer;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Route;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class TicketTransferResource extends Resource
{
    protected static ?string $model = TicketTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Event Specific';

    protected static ?string $navigationParentItem = 'Ticketing';
    
    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Grid::make(1)->schema([
                        Infolists\Components\Section::make([
                            Infolists\Components\TextEntry::make('user.display_name')
                                ->label('Sender')
                                ->url(fn ($record) => UserResource::getUrl('transfers', ['record' => $record->user_id]))
                                ->color('primary')
                                ->iconColor('primary')
                                ->icon('heroicon-m-user'),
                            Infolists\Components\TextEntry::make('recipient_email')
                                ->label('Recipient Email'),
                            Infolists\Components\TextEntry::make('recipient.display_name')
                                ->label('Recipient')
                                ->url(fn ($record) => $record->recipient_user_id ? UserResource::getUrl('transfers', ['record' => $record->recipient_user_id]) : null)
                                ->color('primary')
                                ->iconColor('primary')
                                ->icon('heroicon-m-user'),
                        ])->columns(['md' => 2, 'xl' => 3]),
                        Infolists\Components\Section::make([
                            Infolists\Components\RepeatableEntry::make('purchasedTickets')
                                ->label('Purchased Tickets')
                                ->schema([
                                    Infolists\Components\TextEntry::make('id')
                                        ->label('')
                                        ->url(fn ($state) => PurchasedTicketResource::getUrl('view', ['record' => $state]))
                                        ->formatStateUsing(fn ($record) => sprintf('%s (#%d)',$record->ticketType->name, $record->id))
                                        ->color('primary')
                                        ->iconColor('primary')
                                        ->icon('heroicon-s-ticket'),
                                    Infolists\Components\TextEntry::make('ticketType.addon')
                                        ->label('')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            true => 'Addon',
                                            default => null,
                                        }),
                                ])
                                ->grid(['md' => 2, 'xl' => 3]),
                            Infolists\Components\RepeatableEntry::make('reservedTickets')
                                ->label('Reserved Tickets')
                                ->schema([
                                    Infolists\Components\TextEntry::make('id')
                                        ->label('')
                                        ->url(fn ($state) => ReservedTicketResource::getUrl('view', ['record' => $state]))
                                        ->formatStateUsing(fn ($record) => sprintf('%s (#%d)',$record->ticketType->name, $record->id))
                                        ->color('primary')
                                        ->iconColor('primary')
                                        ->icon('heroicon-s-ticket'),
                                    Infolists\Components\TextEntry::make('ticketType.addon')
                                        ->label('')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            true => 'Addon',
                                            default => null,
                                        }),
                                ])
                                ->grid(['md' => 2, 'xl' => 3]),
                        ]),
                    ]),
                    Infolists\Components\Section::make([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At'),
                        Infolists\Components\IconEntry::make('completed')
                            ->label('Completed')
                            ->icon(fn (bool $state) => match ($state) {
                                true => 'heroicon-o-check-circle',
                                default => 'heroicon-o-x-circle',
                            })
                            ->color(fn (bool $state) => match ($state) {
                                true => 'success',
                                default => 'danger',
                            }),
                    ])->grow(false),
                ])->from('md'),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('user.display_name')
                    ->searchable(['users.display_name', 'users.email'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient_email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipient.display_name')
                    ->label('Recipient')
                    ->searchable(['users.display_name', 'users.email'])
                    ->sortable()
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record->user_id]))
                    ->color('primary')
                    ->icon('heroicon-m-user'),
                Tables\Columns\IconColumn::make('completed')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListTicketTransfers::route('/'),
            'view' => Pages\ViewTicketTransfer::route('/{record}'),
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
            ->whereRelation('purchasedTickets.ticketType', 'event_id', Event::getCurrentEventId())
            ->orWhereRelation('reservedTickets.ticketType', 'event_id', Event::getCurrentEventId());
    }
}
