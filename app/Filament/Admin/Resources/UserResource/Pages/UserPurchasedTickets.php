<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Filament\Admin\Resources\PurchasedTicketResource;
use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserPurchasedTickets extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'purchasedTickets';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $title = 'Purchased Tickets';

    public static function getNavigationLabel(): string
    {
        return 'Purchased Tickets';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('ticketType.name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable()
                    ->label('Order Date'),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order ID')
                    ->sortable()
                    ->url(fn($record) => $record->order_id ? OrderResource::getUrl('view', ['record' => $record->order_id]) : null)
                    ->color('primary')
                    ->icon('heroicon-m-shopping-bag'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn($record) => PurchasedTicketResource::getUrl('view', ['record' => $record->id])),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([])
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
