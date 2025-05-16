<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketTransferResource\Pages;
use App\Filament\Infolists\Components\UserEntry;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Event;
use App\Models\Ticketing\TicketTransfer;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                            UserEntry::make('user')
                                ->userPage('transfers')
                                ->label('Sender'),
                            Infolists\Components\TextEntry::make('recipient_email')
                                ->label('Recipient Email'),
                            UserEntry::make('recipient')
                                ->userPage('transfers')
                                ->label('Recipient'),
                        ])->columns(['md' => 2, 'xl' => 3]),
                        Infolists\Components\Section::make([
                            Infolists\Components\RepeatableEntry::make('purchasedTickets')
                                ->label('Purchased Tickets')
                                ->schema([
                                    Infolists\Components\TextEntry::make('id')
                                        ->label('')
                                        ->url(fn ($state) => PurchasedTicketResource::getUrl('view', ['record' => $state]))
                                        ->formatStateUsing(fn ($record) => sprintf('%s (#%d)', $record->ticketType->name, $record->id))
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
                                        ->formatStateUsing(fn ($record) => sprintf('%s (#%d)', $record->ticketType->name, $record->id))
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
                UserColumn::make('user')
                    ->userPage('transfers'),
                Tables\Columns\TextColumn::make('recipient_email')
                    ->searchable(),
                UserColumn::make('recipient')
                    ->userPage('transfers')
                    ->label('Recipient'),
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
