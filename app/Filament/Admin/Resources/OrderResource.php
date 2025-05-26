<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Infolists\Components\UserEntry;
use App\Filament\Tables\Columns\UserColumn;
use App\Livewire\OrderTicketsTable;
use App\Models\Ticketing\Order;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Section::make([
                        Livewire::make(OrderTicketsTable::class, ['admin' => true]),
                        Fieldset::make('Order Summary')
                            ->schema([
                                TextEntry::make('amount_subtotal')
                                    ->label('Subtotal')
                                    ->money('USD', 100),
                                TextEntry::make('amount_tax')
                                    ->label('Sales Tax')
                                    ->money('USD', 100),
                                TextEntry::make('amount_fees')
                                    ->label('Stripe Fees')
                                    ->money('USD', 100),
                                TextEntry::make('amount_total')
                                    ->label('Total')
                                    ->money('USD', 100),
                            ])->columns(4),
                    ]),
                    Section::make([
                        TextEntry::make('created_at')
                            ->label('Purchase Date')
                            ->dateTime('F jS, Y g:i A T', 'America/New_York'),
                        UserEntry::make('user')
                            ->userPage('orders'),
                        TextEntry::make('user_email'),
                        TextEntry::make('event.name')
                            ->url(fn ($record) => EventResource::getUrl('view', ['record' => $record->event_id]))
                            ->color('primary')
                            ->icon('heroicon-m-calendar')
                            ->iconColor('primary'),
                    ])->grow(false),
                ])->from('lg'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                UserColumn::make('user')
                    ->userPage('orders'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable()
                    ->label('Created At'),
                Tables\Columns\TextColumn::make('amount_total')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->money('USD', 100),
                Tables\Columns\TextColumn::make('refunded')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => match ($state) {
                        true => 'Refunded',
                        default => 'Paid',
                    })
                    ->color(fn (bool $state) => match ($state) {
                        true => 'danger',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->relationship('event', 'name'),
                Filter::make('created_after')
                    ->form([
                        DatePicker::make('created_after'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_after'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['created_after']) {
                            return null;
                        }

                        return 'Created after ' . Carbon::parse($data['created_after'])->toFormattedDateString();
                    }),
                Filter::make('created_before')
                    ->form([
                        DatePicker::make('created_before'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_before'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['created_before']) {
                            return null;
                        }

                        return 'Created before ' . Carbon::parse($data['created_before'])->toFormattedDateString();
                    }),
            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
