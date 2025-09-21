<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\ReservedTicketResource;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Event;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UserReservedTickets extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'reservedTickets';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $title = 'Reserved Tickets';

    public static function getNavigationLabel(): string
    {
        return 'Reserved Tickets';
    }

    protected function getHeaderActions(): array
    {
        /** @var User */
        $user = $this->record;

        return [
            Action::make('create')
                ->label('Give Reserved Ticket')
                ->form([
                    Section::make()
                        ->schema([
                            Forms\Components\Placeholder::make('username')
                                ->label('User')
                                ->content($user->display_name),
                            Forms\Components\Select::make('ticket_type_id')
                                ->options(fn () => Event::getCurrentEvent()?->ticketTypes->pluck('name', 'id')->toArray() ?? [])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?int $state) {
                                    $currentExpiration = $get('expiration_date');
                                    if (! is_null($currentExpiration)) {
                                        return;
                                    }

                                    /** @var ?TicketType $ticketType */
                                    $ticketType = TicketType::find($state);
                                    if (is_null($ticketType)) {
                                        return;
                                    }

                                    if ($ticketType->sale_end_date < now('America/New_York')) {
                                        $set('expiration_date', now('America/New_York')->addWeek()->endOfDay()->format('Y-m-d H:i'));
                                    }
                                }),
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
                                ->hintAction(
                                    FormAction::make('clear')
                                        ->label('Clear')
                                        ->action(fn (Set $set) => $set('expiration_date', null))
                                ),
                            Forms\Components\TextInput::make('count')
                                ->label('Number of Tickets')
                                ->default(1)
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('note')
                                ->label('Note')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) use ($user) {
                    ReservedTicket::create([
                        // Set email so the observer is triggered and emails are sent
                        'email' => $user->email,
                        'ticket_type_id' => $data['ticket_type_id'],
                        'expiration_date' => $data['expiration_date'],
                        'count' => $data['count'],
                        'note' => $data['note'] ?? null,
                    ]);

                    Notification::make()
                        ->title(sprintf('%d Reserved %s Created', $data['count'], Str::plural('Ticket', $data['count'])))
                        ->success()
                        ->send();
                }),
        ];
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
                    ->label('Created At'),
                Tables\Columns\IconColumn::make('isPurchased')
                    ->label('Purchased')
                    ->icon(function (bool $state, ReservedTicket $record) {
                        if ($record->expiration_date < now()) {
                            return 'heroicon-o-clock';
                        }

                        return $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->color(function (bool $state, ReservedTicket $record) {
                        if ($record->expiration_date < now()) {
                            return null;
                        }

                        return $state ? 'success' : 'danger';
                    })
                    ->tooltip(function (bool $state, ReservedTicket $record) {
                        if ($record->expiration_date < now()) {
                            return 'Expired';
                        }

                        return $record->purchasedTicket?->created_at?->timezone('America/New_York')?->format('F jS, Y g:i A T') ?? 'Not Purchased';
                    }),
            ])
            ->filters([
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => ReservedTicketResource::getUrl('view', ['record' => $record->id])),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([]);
    }
}
