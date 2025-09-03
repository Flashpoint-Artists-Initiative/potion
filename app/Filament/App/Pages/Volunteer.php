<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\UserShifts;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use App\Services\VolunteerService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @property Form $form
 */
class Volunteer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.app.pages.volunteer';

    protected static ?string $slug = 'volunteer';

    public bool $signupsEnabled;

    public Carbon $signupStartDate;

    public Carbon $signupEndDate;

    /** @var array<mixed> $data */
    public array $data = [];

    public function mount(): void
    {
        $this->signupsEnabled = Event::getCurrentEvent()->volunteerSignupsOpen ?? false;
        $this->signupStartDate = Event::getCurrentEvent()->volunteerSignupsStart ?? now();
        $this->signupEndDate = Event::getCurrentEvent()->volunteerSignupsEnd ?? now();

        $this->form->fill();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserShifts::class,
        ];
    }

    public function form(Form $form): Form
    {
        $teams = Team::query()->currentEvent()->active()->orderBy('name')->pluck('name', 'id');
        $earliestDate = Shift::query()->orderBy('start_offset')->first()->startCarbon ?? now();
        $latestDate = Shift::query()->orderBy('start_offset', 'desc')->first()->startCarbon ?? now();
        $period = CarbonPeriod::create($earliestDate->toDateString(), $latestDate->toDateString());
        $dateRange = [];

        foreach ($period as $date) {
            $dateRange[$date->toDateString()] = $date->format('D, m/d');
        }

        return $form
            ->schema([
                Select::make('teams')
                    ->options($teams)
                    ->live()
                    ->afterStateUpdated(fn() => $this->resetTable())
                    ->placeholder('Select a Team'),
                Grid::make()
                    ->schema([
                        ToggleButtons::make('start_date')
                            ->options($dateRange)
                            ->required()
                            ->default($earliestDate->toDateString())
                            ->grouped()
                            ->live()
                            ->disableOptionWhen(function (string $value, Get $get) {
                                return Carbon::parse($value)->isAfter(Carbon::parse($get('end_date')));
                            })
                            ->afterStateUpdated(fn() => $this->resetTable()),
                        ToggleButtons::make('end_date')
                            ->options($dateRange)
                            ->required()
                            ->grouped()
                            ->default($latestDate->toDateString())
                            ->live()
                            ->disableOptionWhen(function (string $value, Get $get) {
                                return Carbon::parse($value)->isBefore(Carbon::parse($get('start_date')));
                            })
                            ->afterStateUpdated(fn() => $this->resetTable()),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        $data = $this->form->getState();

        /** @var Event $event */
        $event = Event::getCurrentEvent();
        $startOffset = $event->volunteerBaseDate->diffInMinutes(Carbon::parse($data['start_date'], 'America/New_York'), false);
        $endOffset = $event->volunteerBaseDate->diffInMinutes(Carbon::parse($data['end_date'], 'America/New_York')->addDay()->addMinute(), false);

        $query = Shift::query()
            ->with(['shiftType', 'team'])
            ->where([
                ['start_offset', '>', $startOffset],
                ['start_offset', '<', $endOffset],
            ])
            ->whereHas('team', function (Builder $query) use ($data) {
                // Hide all teams when none are selected
                $query->where('teams.id', $data['teams'] ?? 0);
            });

        return $table
            ->query($query)
            ->defaultSort('startCarbon', 'asc')
            ->emptyStateDescription(fn() => $data['teams'] ? null : 'Select a team')
            ->columns([
                TextColumn::make('shiftType.title')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('startCarbon')
                    ->label('Start Time')
                    ->dateTime('D, m/j g:ia T', 'America/New_York')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('start_offset', $direction)),
                TextColumn::make('endCarbon')
                    ->label('End Time')
                    ->dateTime('D, m/j g:ia T', 'America/New_York'),
                TextColumn::make('lengthInHours')
                    ->label('Duration (Hours)')
                    ->sortable(),
                TextColumn::make('multiplier')
                    ->label('Hours Value')
                    ->sortable()
                    ->formatStateUsing(fn(string $state, Shift $record) => sprintf('%s (%sx)', $record->lengthInHours * $record->multiplier, $state)),
                TextColumn::make('volunteers_count')
                    ->label('Signed Up')
                    ->counts('volunteers')
                    ->formatStateUsing(fn(int $state, ?Shift $record) => sprintf('%d/%d', $state, $record->num_spots ?? 0)),
            ])
            ->actions([
                Action::make('signup')
                    ->button()
                    ->action(function (Shift $record) {
                        $user = Auth::user();
                        if (! $user) {
                            return;
                        }

                        // Signing up
                        if (!$user->shifts->contains($record->id)) {
                            $user->shifts()->attach($record->id);
                            Notification::make()
                                ->title('You have signed up for this shift.')
                                ->success()
                                ->send();
                            // Removing
                        } else {
                            $user->shifts()->detach($record->id);
                            Notification::make()
                                ->title('You have removed a shift from your schedule.')
                                ->success()
                                ->send();
                        }

                        // Refresh the user's shifts so label callback gets latest state
                        $user->load('shifts');
                    })
                    ->label(function (Shift $record, VolunteerService $volunteerService) {
                        $user = Auth::user();
                        if (! $this->signupsEnabled || ! $user) {
                            return 'Unavailable';
                        }

                        if ($user->shifts->contains($record->id)) {
                            return 'Cancel';
                        }

                        if ($record->volunteers_count >= $record->num_spots) {
                            return 'Full';
                        }

                        if ($volunteerService->userHasOverlappingShift($record, $user)) {
                            return 'Conflict';
                        }

                        return 'Sign Up';
                    })
                    ->disabled(function (Shift $record, VolunteerService $volunteerService) {
                        $user = Auth::user();
                        if (! $this->signupsEnabled || ! $user) {
                            return true;
                        }

                        if ($user->shifts->contains($record->id)) {
                            return false;
                        }

                        if ($volunteerService->userHasOverlappingShift($record, $user)) {
                            return true;
                        }

                        if ($record->volunteers_count >= $record->num_spots) {
                            return true;
                        }

                        return false;
                    })
            ]);
    }
}
