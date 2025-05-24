<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        /** @var Event $record */
        return $record->name;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Tabs::make()
                        ->tabs([
                            Tabs\Tab::make('Event')
                                ->schema([
                                    Section::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('location')
                                                ->maxLength(255),
                                            Forms\Components\DatePicker::make('start_date')
                                                ->required()
                                                ->closeOnDateSelection()
                                                ->beforeOrEqual('end_date')
                                                ->helperText('When the gate opens.'),
                                            Forms\Components\DatePicker::make('end_date')
                                                ->required()
                                                ->closeOnDateSelection()
                                                ->afterOrEqual('start_date')
                                                ->helperText('The last public day of the event.'),
                                        ])
                                        ->columns(2),
                                ]),
                            Tabs\Tab::make('Ticketing')
                                ->schema([
                                    Section::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('tickets_per_sale')
                                                ->label('Max Tickets per Sale')
                                                ->required()
                                                ->numeric()
                                                ->default(config('app.defaults.ticketing.tickets_per_sale'))
                                                ->helperText('The maximum number of tickets a user can buy at once.  Does not include reserved tickets or addon tickets.'),
                                        ]),
                                ])
                                ->statePath('settings.ticketing'),
                            Tabs\Tab::make('Art Grants')
                                ->schema([
                                    Section::make()
                                        ->schema([
                                            Forms\Components\Toggle::make('voting_enabled')
                                                ->inline(false)
                                                ->label('Voting Enabled')
                                                ->helperText('Enables public voting for art grants.'),
                                            Forms\Components\DateTimePicker::make('voting_end_date')
                                                ->required()
                                                ->closeOnDateSelection()
                                                ->seconds(false)
                                                ->default(now()->addMinute())
                                                ->helperText('When voting automatically closes.'),
                                            Forms\Components\TextInput::make('dollars_per_vote')
                                                ->label('Dollars per Vote')
                                                ->required()
                                                ->numeric()
                                                ->default(config('app.defaults.art.dollars_per_vote'))
                                                ->helperText('The amount of money each vote is worth.'),
                                            Forms\Components\TextInput::make('votes_per_user')
                                                ->label('Votes per User')
                                                ->required()
                                                ->numeric()
                                                ->default(config('app.defaults.art.votes_per_user'))
                                                ->helperText('The maximum number of votes each user can cast.'),
                                        ])
                                        ->columns(2),
                                ])
                                ->statePath('settings.art'),
                            Tabs\Tab::make('Volunteering')
                                ->schema([
                                    Section::make()
                                        ->schema([
                                            Forms\Components\DateTimePicker::make('signups_start')
                                                ->label('Signup Start Date')
                                                ->required()
                                                ->closeOnDateSelection()
                                                ->seconds(false)
                                                ->default(now()->addMinute())
                                                ->beforeOrEqual('signups_end')
                                                ->helperText('When volunteer signups open.'),
                                            Forms\Components\DateTimePicker::make('signups_end')
                                                ->label('Signup End Date')
                                                ->required()
                                                ->closeOnDateSelection()
                                                ->seconds(false)
                                                ->default(now()->addMinutes(10))
                                                ->afterOrEqual('signups_start')
                                                ->helperText('When volunteer signups close.'),
                                        ])
                                        ->columns(2),
                                ])
                                ->statePath('settings.volunteering'),
                        ])
                        ->contained(false),
                    Grid::make(1)->schema([
                        Section::make([
                            Forms\Components\Toggle::make('active')
                                ->label('Visible to Users'),
                        ]),
                        Section::make('Lockdown')
                            ->schema([
                                Forms\Components\Toggle::make('tickets')
                                    ->label('Tickets')
                                    ->default(config('app.defaults.lockdown.tickets')),
                                Forms\Components\Toggle::make('grants')
                                    ->label('Grants')
                                    ->default(config('app.defaults.lockdown.grants')),
                                Forms\Components\Toggle::make('volunteers')
                                    ->label('Volunteering')
                                    ->default(config('app.defaults.lockdown.volunteers')),
                            ])
                            ->statePath('settings.lockdown')
                            ->hidden(config('app.use_single_lockdown')),
                    ])
                        ->grow(false),
                ])
                    ->from('md'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('Select')
                    ->color('success')
                    ->icon('heroicon-m-arrow-right-circle')
                    ->dispatch('update-active-event', fn (Event $record) => ['eventId' => $record->id])
                    ->tooltip('Use this event for the event-specific resources'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    BulkAction::make('make-active')
                        ->label('Mark as Active')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->toQuery()->update(['active' => true])),
                    BulkAction::make('make-inactive')
                        ->label('Mark as Inactive')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->toQuery()->update(['active' => false])),
                ]),
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'waiver' => Pages\ViewWaiver::route('/{record}/waiver'),
            'edit-waiver' => Pages\EditWaiver::route('/{record}/waiver/edit'),
            'content' => Pages\EditPageContent::route('/{record}/content'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewEvent::class,
            Pages\EditEvent::class,
            Pages\ViewWaiver::class,
            Pages\EditPageContent::class,
        ]);
    }
}
