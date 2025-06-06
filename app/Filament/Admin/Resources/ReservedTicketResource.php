<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReservedTicketResource\Pages;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Event;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class ReservedTicketResource extends Resource
{
    protected static ?string $model = ReservedTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Event Specific';

    protected static ?string $navigationParentItem = 'Ticketing';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        if ($form->getOperation() === 'create') {
            return $form
                ->schema(self::getFormSections());
        }

        // Editing
        return $form
            ->schema([
                Split::make([
                    ...self::getFormSections(),
                    self::getInfoSection(),
                ])
                    ->from('md'),
            ])
            ->columns(1);
    }

    /**
     * @return Section[]
     */
    protected static function getFormSections(): array
    {
        return [
            Section::make([
                Forms\Components\Select::make('ticket_type_id')
                    ->disabled(fn ($operation) => $operation !== 'create')
                    ->relationship(
                        name: 'ticketType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('event_id', Event::getCurrentEventId()),
                    )
                    ->required()
                    ->reactive(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->required(fn ($operation) => $operation == 'create')
                    ->disabled(fn (?ReservedTicket $record) => $record?->user_id !== null)
                    ->hintAction(
                        Actions\Action::make('findEmailByUser')
                            ->label('Search users by email')
                            ->icon('heroicon-o-magnifying-glass')
                            ->form([
                                Forms\Components\Select::make('userId')
                                    ->relationship('user', 'email')
                                    ->searchable(['display_name', 'email'])
                                    ->getOptionLabelFromRecordUsing(fn (User $user) => sprintf('%s (%s)', $user->display_name, $user->email)),
                            ])
                            ->action(function (array $data, Set $set) {
                                $set('email', User::findOrFail((int) $data['userId'])->email);
                            })
                            ->hidden(fn ($operation) => $operation !== 'create'),
                    ),
                Forms\Components\DateTimePicker::make('expiration_date')
                    ->timezone('America/New_York')
                    ->helperText(function ($get) {
                        /** @var TicketType $ticketType */
                        $ticketType = TicketType::find($get('ticket_type_id'));
                        if (is_null($ticketType)) {
                            return '';
                        }

                        return sprintf('Defaults to ticket sale end date: %s', $ticketType->sale_end_date?->timezone('America/New_York')->format('F jS, Y g:i A T') ?? '');
                    })
                    ->format('Y-m-d H:i:s')
                    ->seconds(false)
                    ->closeOnDateSelection()
                    ->disabled(fn (?ReservedTicket $record) => $record?->is_purchased),
                Forms\Components\TextInput::make('note')
                    ->helperText('Use this for the name of the art project, theme camp, or other special note. User will see this.')
                    ->maxLength(255)
                    ->disabled(fn (?ReservedTicket $record) => $record?->is_purchased),
            ])
                ->columns(2),
        ];
    }

    protected static function getInfoSection(): Section
    {
        return Section::make([
            Forms\Components\Placeholder::make('Associated User')
                ->content(function (ReservedTicket $record) {
                    if ($record->user_id) {
                        return Action::make()
                            ->label($record->user->display_name)
                            ->icon('heroicon-m-user')
                            ->url(UserResource::getUrl('reserved', ['record' => $record->user_id]))
                            ->link();
                    }
                })
                ->hidden(fn (ReservedTicket $record) => ! $record->user_id),
            Forms\Components\Placeholder::make('Purchased Ticket')
                ->content(function (ReservedTicket $record) {
                    if ($record->is_purchased) {
                        return Action::make('get-purchased-ticket')
                            ->label($record->purchasedTicket?->created_at?->timezone('America/New_York')->format('F jS, Y g:i A T'))
                            ->icon('heroicon-s-ticket')
                            ->url(PurchasedTicketResource::getUrl('view', ['record' => $record->purchasedTicket?->id]))
                            ->link();
                    }
                })
                ->hidden(fn (?ReservedTicket $record) => ! $record?->is_purchased),
            Forms\Components\Placeholder::make('Created At')
                ->content(fn (?ReservedTicket $record) => $record?->created_at?->timezone('America/New_York')->format('F jS, Y g:i A T') ?? ''),
            Forms\Components\Placeholder::make('Updated At')
                ->content(fn (?ReservedTicket $record) => $record?->updated_at?->timezone('America/New_York')->format('F jS, Y g:i A T') ?? ''),
        ])
            ->grow(false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['ticketType', 'user', 'purchasedTicket']))
            ->columns([
                Tables\Columns\TextColumn::make('ticketType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                UserColumn::make('user')
                    ->userPage('reserved'),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ticketType')
                    ->label('Ticket Type')
                    ->relationship('ticketType', 'name')
                    ->multiple()
                    ->preload()
                    ->placeholder('All Ticket Types'),
                TernaryFilter::make('is_purchased')
                    ->label('Purchased Status')
                    ->placeholder('All Purchased Status')
                    ->trueLabel('Purchased')
                    ->falseLabel('Not Purchased')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('purchasedTicket'),
                        false: fn (Builder $query) => $query->whereDoesntHave('purchasedTicket'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(fn (ReservedTicket $record): bool => ! $record->is_purchased)
            ->emptyStateHeading(fn () => Event::getCurrentEvent() ? 'No Reserved Tickets' : 'No Event Selected');
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
            'index' => Pages\ListReservedTickets::route('/'),
            'create' => Pages\CreateReservedTicket::route('/create'),
            'view' => Pages\ViewReservedTicket::route('/{record}'),
            'edit' => Pages\EditReservedTicket::route('/{record}/edit'),
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
