<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CartStatusEnum;
use App\Filament\Admin\Resources\CartResource\Pages;
use App\Filament\Infolists\Components\UserEntry;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Ticketing\Cart;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationParentItem = 'Orders';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make([
                    UserEntry::make('user')
                        ->userPage('carts'),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('F jS, Y g:i A T', 'America/New_York'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Status')
                        ->formatStateUsing(fn (Cart $record, CartStatusEnum $state) => match ($state) {
                            CartStatusEnum::Active => 'Expires ' . Carbon::parse($record->expiration_date)
                                ->diffForHumans(),
                            CartStatusEnum::Expired => 'Expired',
                            CartStatusEnum::Completed => sprintf('Completed (%s)',
                                Blade::render('<x-filament::link href="{{ $url }}" color="primary">{{ $label }}</x-filament::link>', [
                                    'url' => OrderResource::getUrl('view', ['record' => $record->order?->id]),
                                    'label' => 'Order #' . $record->order?->id,
                                ])
                            ),
                        })
                        ->html(),
                    Infolists\Components\TextEntry::make('quantity')
                        ->label('Tickets in Cart'),
                    Infolists\Components\TextEntry::make('subtotal')
                        ->money('usd', 100),
                    Infolists\Components\RepeatableEntry::make('items')
                        ->schema([
                            Infolists\Components\TextEntry::make('ticketType.name')
                                ->label('Ticket Type'),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('Quantity'),
                            Infolists\Components\TextEntry::make('ticketType.price')
                                ->label('Price')
                                ->money('usd'),
                        ])->columns(3)->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Forms\Components\Select::make('user_id')
    //                 ->relationship('user', 'id')
    //                 ->required(),
    //             Forms\Components\DateTimePicker::make('expiration_date')
    //                 ->required(),
    //             Forms\Components\TextInput::make('stripe_checkout_id')
    //                 ->maxLength(255),
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order']))
            ->columns([
                UserColumn::make('user')
                    ->userPage('carts'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (Cart $record, CartStatusEnum $state) => match ($state) {
                        CartStatusEnum::Active => 'Expires ' . Carbon::parse($record->expiration_date)
                            ->diffForHumans(),
                        CartStatusEnum::Expired => 'Expired',
                        CartStatusEnum::Completed => 'Completed',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Tickets in Cart')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('usd', 100),
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
            'index' => Pages\ListCarts::route('/'),
            'view' => Pages\ViewCart::route('/{record}'),
        ];
    }
}
