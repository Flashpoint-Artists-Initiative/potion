<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketTypeResource\Pages;
use App\Models\Event;
use App\Models\Ticketing\TicketType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class TicketTypeResource extends Resource
{
    protected static ?string $model = TicketType::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Event Specific';

    protected static ?string $navigationLabel = 'Ticketing';

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return Event::where('id', Event::getCurrentEventId())->exists();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Placeholder::make('event.name')
                    ->label('Event')
                    ->content(fn (?TicketType $record) => $record->event->name ?? Event::getCurrentEvent()->name ?? 'No Event'),
                Forms\Components\DateTimePicker::make('sale_start_date')
                    ->required()
                    ->seconds(false)
                    ->closeOnDateSelection()
                    ->before('sale_end_date'),
                Forms\Components\DateTimePicker::make('sale_end_date')
                    ->required()
                    ->seconds(false)
                    ->closeOnDateSelection()
                    ->afterOrEqual('sale_start_date')
                    ->helperText('Reserved tickets will expire after this date, by default.'),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('0 = Unlimited. Unlimited tickets will not be shown on the main page.'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->disabled(fn (?TicketType $record) => $record?->purchasedTickets()->exists() ?? false)
                    ->helperText('Price cannot be changed once tickets have been sold'),
                Forms\Components\Textarea::make('description')
                    // ->required()
                    ->default('')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('active')
                    ->required()
                    ->label('Public')
                    ->helperText('Public tickets will be available for purchase on the main page. Private tickets are for direct sale only.'),
                Forms\Components\Toggle::make('transferable')
                    ->required()
                    ->default(true)
                    ->helperText('Transferable tickets can be transferred to another user.'),
                Forms\Components\Toggle::make('addon')
                    ->label('Add-on')
                    ->required()
                    ->helperText('Add-on tickets do not count as a ticket for attending the event. They are only for add-on items, such as child tickets, ice sales, etc.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sale_start_date')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_end_date')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => $state === 0 ? '∞' : $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remainingTicketCount')
                    ->numeric()
                    ->formatStateUsing(fn ($state, TicketType $record) => ($record->quantity === 0 && $state === 0) ? '∞' : $state)
                    ->label('Remaining'),
                Tables\Columns\IconColumn::make('active')
                    ->label('Public')
                    ->boolean(),
                Tables\Columns\IconColumn::make('transferable')
                    ->boolean(),
                Tables\Columns\IconColumn::make('addon')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Mark as Active')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each->update(['active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Mark as Inactive')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each->update(['active' => false])),
                ]),
            ])
            ->emptyStateHeading(fn () => Event::getCurrentEvent() ? 'No Ticket Types' : 'No Event Selected');
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
            'index' => Pages\ListTicketTypes::route('/'),
            'create' => Pages\CreateTicketType::route('/create'),
            'view' => Pages\ViewTicketType::route('/{record}'),
            'edit' => Pages\EditTicketType::route('/{record}/edit'),
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
            ->where('event_id', Event::getCurrentEventId());
    }
}
