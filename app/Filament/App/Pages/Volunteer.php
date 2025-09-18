<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\UserShifts;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
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
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Nette\Utils\Html;

/**
 * @property Form $form
 */
class Volunteer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.app.pages.volunteer';

    protected static ?string $slug = 'volunteer/{id?}';

    public bool $signupsEnabled;

    public Carbon $signupStartDate;

    public Carbon $signupEndDate;

    public bool $hasTicket;

    public int $teamId;

    /** @var array<mixed> $data */
    public array $data = [];

    public function getTitle(): string|Htmlable
    {
        if ($this->teamId) {
            $team = Team::find($this->teamId);
            if ($team) {
                return 'Volunteer Signups - ' . $team->name;
            }
        }
        return 'Volunteer Signups';
    }

    public function mount(?int $id = null): void
    {
        $this->teamId = $id ?? 0;
        $this->signupsEnabled = Event::getCurrentEvent()->volunteerSignupsOpen ?? false;
        $this->signupStartDate = Event::getCurrentEvent()->volunteerSignupsStart ?? now();
        $this->signupEndDate = Event::getCurrentEvent()->volunteerSignupsEnd ?? now();

        $this->hasTicket = Auth::user() && Auth::user()->getValidTicketsForEvent()->isNotEmpty();

        $this->form->fill();
    }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         UserShifts::class,
    //     ];
    // }

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
                Select::make('start_date')
                    ->options($dateRange)
                    ->required()
                    ->default($earliestDate->toDateString())
                    ->live()
                    ->disableOptionWhen(function (string $value, Get $get) {
                        return Carbon::parse($value)->isAfter(Carbon::parse($get('end_date')));
                    })
                    ->afterStateUpdated(fn() => $this->resetTable()),
                Select::make('end_date')
                    ->options($dateRange)
                    ->required()
                    ->default($latestDate->toDateString())
                    ->live()
                    ->disableOptionWhen(function (string $value, Get $get) {
                        return Carbon::parse($value)->isBefore(Carbon::parse($get('start_date')));
                    })
                    ->afterStateUpdated(fn() => $this->resetTable()),
                // TODO: Get these to work on responsive layouts
                // ToggleButtons::make('start_date')
                //     ->options($dateRange)
                //     ->required()
                //     ->default($earliestDate->toDateString())
                //     ->grouped()
                //     ->live()
                //     ->disableOptionWhen(function (string $value, Get $get) {
                //         return Carbon::parse($value)->isAfter(Carbon::parse($get('end_date')));
                //     })
                //     ->afterStateUpdated(fn() => $this->resetTable()),
                // ToggleButtons::make('end_date')
                //     ->options($dateRange)
                //     ->required()
                //     ->grouped()
                //     ->default($latestDate->toDateString())
                //     ->live()
                //     ->disableOptionWhen(function (string $value, Get $get) {
                //         return Carbon::parse($value)->isBefore(Carbon::parse($get('start_date')));
                //     })
                //     ->afterStateUpdated(fn() => $this->resetTable()),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function teamsInfolist(Infolist $infolist): Infolist
    {
        $teams = Team::query()->currentEvent()->active()->orderBy('name')->get();

        return $infolist
            ->state(['teams' => $teams])
            ->schema([
                RepeatableEntry::make('teams')
                    ->label(new HtmlString('<h1 class="text-2xl">Teams</h1>'))
                    ->schema([
                        Section::make(fn(Team $state) => $state->name)
                            ->headerActions([
                                InfolistAction::make('shifts')
                                    ->label('View Shifts')
                                    ->url(fn(Team $record) => self::getUrl(['id' => $record->id])),
                            ])
                            ->schema([
                                TextEntry::make('description')
                                    ->label('')
                                    ->html(),
                            ]),
                    ]),
            ]);
    }

    public function shiftTypesInfolist(Infolist $infolist): Infolist
    {
        $shiftTypes = ShiftType::query()
            ->whereHas('team', function (Builder $query) {
                $query->where('teams.id', $this->teamId);
            })
            ->orderBy('title')
            ->get();

        $signupNote = Team::find($this->teamId)->signup_note ?? null;

        $state = ['shiftTypes' => $shiftTypes];

        if ($signupNote) {
            $state['signupNote'] = $signupNote;
        }

        return $infolist
            ->state($state)
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('signupNote')
                            ->label('')
                            ->html()
                    ])
                    ->visible(fn() => ! empty($signupNote)),
                Section::make('Available Positions')
                    ->description('These are the different types of volunteer positions available for this team. You can sign up for any position when signing up for shifts.')
                    ->collapsible()
                    ->persistCollapsed()
                    ->schema([
                        RepeatableEntry::make('shiftTypes')
                            ->label('')
                            ->schema([
                                TextEntry::make('description')
                                    ->label(fn(ShiftType $record) => $record->title),
                                InfolistGrid::make(6)
                                    ->schema([
                                        TextEntry::make('shadeProvided')
                                            ->label('Shade Provided')
                                            ->icon(function (ShiftType $record) {
                                                return match ($record->shadeProvided) {
                                                    'note' => 'heroicon-o-exclamation-circle',
                                                    "1" => 'heroicon-o-check-circle',
                                                    "0" => 'heroicon-o-x-circle',
                                                    default => 'heroicon-o-question-mark-circle',
                                                };
                                            })
                                            ->iconColor(function (ShiftType $record) {
                                                return match ($record->shadeProvided) {
                                                    'note' => 'warning',
                                                    "1" => 'success',
                                                    "0" => 'danger',
                                                    default => 'warning',
                                                };
                                            })
                                            ->getStateUsing(function (ShiftType $record) {
                                                return match ($record->shadeProvided) {
                                                    'note' => $record->shadeProvidedNote,
                                                    "1" => 'Yes',
                                                    "0" => 'No',
                                                    default => 'Unknown',
                                                };
                                            }),
                                        TextEntry::make('longStanding')
                                            ->label('Long Periods of Standing')
                                            ->icon(function (ShiftType $record) {
                                                return match ($record->longStanding) {
                                                    'note' => 'heroicon-o-exclamation-circle',
                                                    "1" => 'heroicon-o-check-circle',
                                                    "0" => 'heroicon-o-x-circle',
                                                    default => 'heroicon-o-question-mark-circle',
                                                };
                                            })
                                            ->iconColor(function (ShiftType $record) {
                                                return match ($record->longStanding) {
                                                    'note' => 'warning',
                                                    "1" => 'success',
                                                    "0" => 'danger',
                                                    default => 'warning',
                                                };
                                            })
                                            ->getStateUsing(function (ShiftType $record) {
                                                return match ($record->longStanding) {
                                                    'note' => $record->longStandingNote,
                                                    "1" => 'Yes',
                                                    "0" => 'No',
                                                    default => 'Unknown',
                                                };
                                            }),
                                        TextEntry::make('physicalRequirementsNote')
                                            ->label('Physical Requirements')
                                            ->hidden(fn(ShiftType $record) => empty($record->physicalRequirementsNote)),
                                    ]),
                            ]),
                    ]),
            ]);
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
            ->whereHas('team', function (Builder $query) {
                // Hide all teams when none are selected
                $query->where('teams.id', $this->teamId);
            });

        return $table
            ->query($query)
            ->defaultSort('startCarbon', 'asc')
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
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('length', $direction)),
                TextColumn::make('multiplier')
                    ->label('Hours Value')
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
                        if (! $this->signupsEnabled || ! $user || ! $this->hasTicket) {
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
            ], position: ActionsPosition::BeforeColumns);
    }
}
