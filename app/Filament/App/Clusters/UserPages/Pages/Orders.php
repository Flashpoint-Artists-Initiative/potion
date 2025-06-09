<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Filament\App\Clusters\UserPages;
use App\Models\Ticketing\Order;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Orders extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.app.clusters.user-pages.pages.orders';

    protected static ?string $cluster = UserPages::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->currentUser())
            ->columns([
                TextColumn::make('id')
                    ->label('Order Number')
                    ->sortable(),
                TextColumn::make('event.name')
                    ->label('Event')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Purchase Date')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York'),
                TextColumn::make('refunded')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Refunded' : 'Complete'),
                TextColumn::make('amount_total')
                    ->label('Total')
                    ->money('usd', 100)
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Order $record) => view('filament.app.clusters.user-pages.modals.orders-modal', [
                        'order' => $record,
                    ]))
                    ->modalCancelAction(false)
                    ->modalSubmitActionLabel('Close'),
            ]);
    }
}
