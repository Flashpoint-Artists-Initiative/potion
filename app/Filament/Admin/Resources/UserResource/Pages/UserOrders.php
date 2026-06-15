<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Ticketing\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class UserOrders extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'orders';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationLabel(): string
    {
        return 'Orders';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id'),
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
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Order $record): string => ViewOrder::getUrl(['record' => $record->id])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
