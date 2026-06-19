<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Widgets;

use App\Filament\Admin\Resources\ShiftResource;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction as ActionsCreateAction;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Guava\Calendar\Attributes\CalendarSchema;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Enums\Context;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\DeleteAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\DateSelectInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\EventResizeInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class ShiftCalendarWidget extends CalendarWidget
{
    private const int SLOT_MINUTES = 15;

    public Team $record;

    protected CalendarViewType $calendarView = CalendarViewType::TimeGridWeek;

    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected bool $eventResizeEnabled = true;

    public function mount(): void
    {
        $this->record->load(['event', 'shiftTypes']);
    }

    /**
     * Get the event model
     */
    protected function event(): Event
    {
        return $this->record->event;
    }

    /**
     * Get the timezone of the event
     */
    protected function eventTimezone(): string
    {
        return $this->event()->timezone;
    }

    /**
     * Override the default context menu actions to pass the context data to the actions.
     *
     * @param  array<string, mixed>  $data
     */
    public function getContextMenuActionsUsing(Context $context, array $data = []): Collection
    {
        $this->setRawCalendarContextData($context, $data);

        $actions = match ($context) {
            Context::EventClick => $this->getCachedEventClickContextMenuActions(),
            Context::DateClick => $this->getCachedDateClickContextMenuActions(),
            Context::DateSelect => $this->getCachedDateSelectContextMenuActions(),
            Context::NoEventsClick => $this->getCachedNoEventsClickContextMenuActions(),
            default => [],
        };

        return collect($actions)
            ->filter(fn (Action $action) => $action->isVisible())
            ->map(function (Action $action): string {
                $raw = $this->getRawCalendarContextData();
                /** @var array<string, mixed> $contextArguments */
                $contextArguments = is_array($raw) ? $raw : [];

                return ($action)($contextArguments)->toHtml();
            });
    }

    /**
     * Static options for the calendar widget.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'date' => $this->event()->start_date,
            'headerToolbar' => ['start' => 'title', 'center' => 'resourceTimeGridDay,resourceTimelineWeek,timeGridWeek', 'end' => 'prev,next'],
            'firstDay' => $this->event()->startDateCarbon->dayOfWeek,
            'slotDuration' => sprintf('00:%02d:00', self::SLOT_MINUTES),
            'pointer' => true,
            'duration' => ['days' => $this->event()->startDateCarbon->diffInDays($this->event()->endDateCarbon) + 1],
            'views' => [
                'resourceTimeGridDay' => [
                    'duration' => ['days' => 1],
                ],
                'buttonText' => [
                    'close' => 'Closed',
                    'resourceTimeGridDay' => 'Day',
                    'resourceTimelineWeek' => 'Week',
                    'timeGridWeek' => 'Week',
                ],
            ],
        ];
    }

    /**
     * Get the events for the calendar widget.
     *
     * @return array<int, mixed>|Collection<int, Shift|CalendarEvent>|Builder
     */
    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        /** @var Collection<int, Shift|CalendarEvent> $events */
        $events = collect(
            Shift::query()
                ->whereHas('shiftType', fn (Builder $query) => $query->where('team_id', $this->record->id))
                ->get()
                ->all()
        );

        return $events->push(
            CalendarEvent::make()
                ->title('Event Duration')
                ->start($this->event()->start_date)
                ->end($this->event()->endDateCarbon->addDays(1)->subMinute()->format('Y-m-d H:i:s'))
                ->allDay()
                ->startEditable(false)
                ->durationEditable(false)
                ->classNames(['cursor-default'])
        );
    }

    /**
     * The resources for the calendar widget (What to group by in resource view)
     *
     * @return Collection<int, ShiftType>|ShiftType[]
     */
    protected function getResources(): Collection|array|Builder
    {
        return $this->record->shiftTypes;
    }

    /**
     * Handles click & drag events
     */
    protected function onDateSelect(DateSelectInfo $info): void
    {
        if (! Auth::user()?->can('create', Shift::class)) {
            return;
        }

        $this->mountAction('createShift');
    }

    /**
     * Context menu actions for date click events
     *
     * @return Action[]
     */
    protected function getDateClickContextMenuActions(): array
    {
        return $this->record->shiftTypes->map(function (ShiftType $shiftType) {
            return Action::make('createClick-' . $shiftType->title)
                ->label('New ' . $shiftType->title . ' Shift')
                ->model(Shift::class)
                ->icon('heroicon-o-plus')
                ->action(function (self $livewire) use ($shiftType): void {
                    $arguments = $livewire->getMountedAction()?->getArguments() ?? [];
                    $dateStr = data_get($arguments, 'data.dateStr');

                    if (! is_string($dateStr) || $dateStr === '') {
                        return;
                    }

                    $startOffset = $this->event()->roundedMinutesFromVolunteerBase($dateStr, self::SLOT_MINUTES);

                    Shift::create([
                        'start_offset' => $startOffset,
                        'length' => $shiftType->length,
                        'num_spots' => $shiftType->num_spots,
                        'shift_type_id' => $shiftType->id,
                        'multiplier' => 1,
                    ]);
                })
                ->after(fn (self $livewire) => $livewire->refreshRecords());
        })->all();
    }

    /**
     * Handle drag & drop events
     */
    protected function onEventDrop(EventDropInfo $info, Model $event): bool
    {
        if (! $event instanceof Shift) {
            return false;
        }

        if ((int) $this->getRawCalendarContextData('delta.seconds') === 0) {
            return false;
        }

        if ($event->volunteers_count > 0) {
            $this->mountAction('confirmEdit', ['action' => 'doEventDrop']);

            return false;
        }

        return $this->doEventDrop();
    }

    /**
     * Process the drag & drop event by updating the shift start offset
     */
    protected function doEventDrop(): bool
    {
        /** @var Shift $shift */
        $shift = $this->getEventRecord();

        $shift->start_offset += (int) $this->getRawCalendarContextData('delta.seconds') / 60;

        try {
            $shift->save();
        } catch (QueryException) {
            return false;
        }

        return true;
    }

    /**
     * Handle resize events
     */
    protected function onEventResize(EventResizeInfo $info, Model $event): bool
    {
        if (! $event instanceof Shift) {
            return false;
        }

        if ($event->volunteers_count > 0) {
            $this->mountAction('confirmEdit', ['action' => 'doEventResize']);

            return false;
        }

        return $this->doEventResize();
    }

    /**
     * Process the resize event by updating the shift length
     */
    protected function doEventResize(): bool
    {
        /** @var Shift $shift */
        $shift = $this->getEventRecord();

        $shift->length += max(0, intdiv((int) $this->getRawCalendarContextData('endDelta.seconds'), 60));

        try {
            $shift->save();
        } catch (QueryException) {
            return false;
        }

        return true;
    }

    /**
     * Context menu actions for event click events
     *
     * @return Action[]
     */
    protected function getEventClickContextMenuActions(): array
    {
        return [
            Action::make('view')
                ->label('Manage Shift')
                ->icon('heroicon-o-adjustments-horizontal')
                ->action(function (self $livewire): void {
                    /** @var Shift $shift */
                    $shift = $livewire->getEventRecord();

                    redirect()->to(ShiftResource::getRecordUrl('view', $shift));
                }),
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    /**
     * Mounted action for creating a shift when a date is clicked & dragged
     *
     * when determining the start_offset, there are two paths:
     * 1. The start date is generated from the calendar selection
     * 2. The start date is generated from the form state
     *
     * If the start date is generated from the calendar selection (calendar_start_offset), we can use the start offset directly.
     * If the start date is generated from the form state (start_datetime), we need to round the start date to the nearest slot
     */
    public function createShiftAction(): CreateAction
    {
        return $this->createAction(Shift::class, 'createShift')
            ->fillForm(function (DateSelectInfo $info, self $livewire): array {
                $start = $livewire->calendarSelectionDate('startStr');
                $end = $livewire->calendarSelectionDate('endStr')->roundMinutes(self::SLOT_MINUTES);
                $startOffset = $livewire->event()->roundedMinutesFromVolunteerBase($start, self::SLOT_MINUTES);
                $snappedStart = $livewire->event()->volunteerDateTimeFromOffset($startOffset);

                return [
                    'start_datetime' => $livewire->formatVolunteerDateTimeForPickerState($snappedStart),
                    'calendar_start_offset' => $startOffset,
                    'length_in_hours' => max(0.25, $snappedStart->diffInMinutes($end) / 60),
                    'multiplier' => '1',
                    ...$livewire->calendarSelectionShiftTypeDefaults($info),
                ];
            })
            // Mutate the data before saving to set the model start_offset based on the calendar selection or form state
            ->mutateDataUsing(function (array $data, self $livewire): array {
                if (array_key_exists('calendar_start_offset', $data)) {
                    $data['start_offset'] = (int) $data['calendar_start_offset'];
                } elseif (array_key_exists('start_datetime', $data)) {
                    $data['start_offset'] = $livewire->event()->roundedMinutesFromVolunteerBase((string) $data['start_datetime'], self::SLOT_MINUTES);
                }

                unset($data['calendar_start_offset'], $data['start_datetime']);

                return $data;
            });
    }

    /**
     * Set the shift defaults in the form based on the selected shift type
     *
     * @return array<string, mixed>
     */
    protected function calendarSelectionShiftTypeDefaults(DateSelectInfo $info): array
    {
        if ($info->resource === null) {
            return [];
        }

        $shiftType = ShiftType::query()->find($info->resource->getId());

        if ($shiftType === null) {
            return [];
        }

        return [
            'shift_type_id' => $shiftType->id,
            'num_spots' => $shiftType->num_spots,
        ];
    }

    /**
     * Get the date from the calendar selection or event info object
     */
    protected function calendarSelectionDate(string $key): Carbon
    {
        $value = $this->getRawCalendarContextData($key)
            ?? data_get($this->getMountedAction()?->getArguments(), "data.{$key}");

        // This should never happen, but if it does, throw an exception
        if (! is_string($value) || $value === '') {
            throw new \InvalidArgumentException('Error getting date from calendar selection');
        }

        return Carbon::parse($value, $this->eventTimezone());
    }

    /**
     * Format the form datetime picker state
     */
    protected function formatVolunteerDateTimeForPickerState(Carbon $dateTime): string
    {
        return $dateTime
            ->copy()
            ->timezone($this->eventTimezone())
            ->utc()
            ->format('Y-m-d H:i:s T');
    }

    /**
     * Confirm edit action for shift modification via drag & drop or resize
     */
    public function confirmEditAction(): Action
    {
        return Action::make('confirmEdit')
            ->requiresConfirmation()
            ->modalHeading('Confirm Shift Change')
            ->modalSubmitActionLabel('Save and Notify')
            ->modalDescription(new HtmlString('This shift has volunteers signed up. Are you sure you want to change the start time?<br>
            Volunteers will be notified of this change. To save without notifying them, click the event and open the edit modal, make the changes, then click "Save Quietly".'))
            ->action(function (array $arguments): void {
                match ($arguments['action'] ?? null) {
                    'doEventDrop' => $this->doEventDrop(),
                    'doEventResize' => $this->doEventResize(),
                    default => null,
                };
            })
            ->after(fn (self $livewire) => $livewire->refreshRecords());
    }

    /**
     * Edit action for the shift context menu
     */
    public function editAction(): EditAction
    {
        return parent::editAction()
            ->modalHeading(fn (Shift $record) => sprintf(
                'Edit Shift #%d (%d %s)',
                $record->id,
                $record->volunteers_count,
                str('signup')->plural($record->volunteers_count)
            ))
            ->modalSubmitActionLabel(fn (Shift $record) => $record->volunteers_count > 0 ? 'Save and Notify' : 'Save')
            ->mutateFormDataUsing(function (array $data, array $arguments, Shift $record) {
                if ($arguments['quietly'] ?? false) {
                    $record->dontNotifyVolunteers();
                }

                return $data;
            })
            ->extraModalFooterActions(fn (EditAction $action, Shift $record) => $record->volunteers_count > 0 ? [
                $action->makeModalSubmitAction('saveQuietly', ['quietly' => true])
                    ->color('primary')
                    ->label('Save Quietly'),
            ] : []);
    }

    /**
     * Delete action for the shift context menu
     */
    public function deleteAction(): DeleteAction
    {
        return parent::deleteAction()
            ->modalDescription(function (Shift $record) {
                $str = 'Are you sure you want to delete this shift?';
                if ($record->volunteers_count > 0) {
                    $str .= sprintf(
                        '<br>%d %s signed up for this shift.',
                        $record->volunteers_count,
                        str('volunteer')->plural($record->volunteers_count)
                    );
                }

                return new HtmlString($str);
            })
            ->modalSubmitActionLabel(fn (Shift $record) => $record->volunteers_count > 0 ? 'Delete and Notify' : 'Delete')
            ->extraModalFooterActions(fn (Shift $record) => $record->volunteers_count > 0 ? [
                DeleteAction::make('deleteQuietly')
                    ->label('Delete Quietly')
                    ->modalHeading(null)
                    ->successNotificationTitle('Deleted quietly')
                    ->using(fn (Shift $record) => $record->dontNotifyVolunteers()->delete())
                    ->requiresConfirmation(false),
            ] : []);
    }

    /**
     * Form schema for the shift modal
     */
    #[CalendarSchema(model: Shift::class)]
    protected function shiftSchema(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make()
                ->schema([
                    Components\Select::make('shift_type_id')
                        ->label('Shift Type')
                        ->options(fn () => ShiftType::query()
                            ->where('team_id', $this->record->id)
                            ->orderBy('title')
                            ->pluck('title', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(condition: fn ($operation) => $operation == 'createShift')
                        ->afterStateUpdated(function ($state, $set, $operation) {
                            $shiftType = ShiftType::query()->where('id', $state)->firstOrFail();
                            if ($operation == 'createShift') {
                                $set('num_spots', $shiftType->num_spots);
                            }
                        }),
                    Components\Hidden::make('calendar_start_offset')
                        ->visible(fn (string $operation): bool => $operation === 'createShift'),
                    Components\DateTimePicker::make('start_datetime')
                        ->label('Start Time')
                        ->timezone(fn (): string => $this->eventTimezone())
                        ->required()
                        ->seconds(false)
                        ->step(self::SLOT_MINUTES * 60)
                        ->format('Y-m-d H:i:s T')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, self $livewire, string $operation): void {
                            if ($operation !== 'createShift' || ! is_string($state) || $state === '') {
                                return;
                            }

                            $set('calendar_start_offset', $livewire->event()->roundedMinutesFromVolunteerBase($state, self::SLOT_MINUTES));
                        }),
                    Components\TextInput::make('length_in_hours')
                        ->label('Length (hours)')
                        ->numeric()
                        ->minValue(.25)
                        ->step(.25)
                        ->required(),
                    Components\TextInput::make('num_spots')
                        ->label('Number of People')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1),
                    Components\Select::make('multiplier')
                        ->label('Multiplier')
                        ->options([
                            '1' => '1x',
                            '1.5' => '1.5x',
                            '2' => '2x',
                        ])
                        ->required()
                        ->selectablePlaceholder(false)
                        ->helperText('Used to determine how many hours a volunteer will receive for this shift'),
                ]),
            Components\Placeholder::make('warning')
                ->label('')
                ->content(new HtmlString(Blade::render(
                    '<x-notification-banner type="warning">{{$text}}</x-notification-banner>',
                    ['text' => 'WARNING: Volunteers have signed up for this shift. Any changes to this shift will affect their schedule. Click "Save Quietly" to save changes without notifying volunteers.']
                )))
                ->visible(fn ($record) => ($record->volunteers_count ?? 0) > 0)
                ->columnSpanFull(),
            Components\TextInput::make('changeReason')
                ->label('Reason for Change')
                ->placeholder('Optional')
                ->helperText('This will be included in the notification to volunteers')
                ->columnSpanFull()
                ->visible(fn ($record) => ($record->volunteers_count ?? 0) > 0),
        ]);
    }

    /**
     * Header actions for the calendar widget
     * Currently the create shift button and form
     *
     * @return array<mixed>
     */
    public function getHeaderActions(): array
    {
        return [
            ActionsCreateAction::make('createShiftType')
                ->model(ShiftType::class)
                ->label('New Shift Type')
                ->schema([
                    Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Components\TextInput::make('location')
                        ->label('Check-in Location')
                        ->required()
                        ->maxLength(255),
                    Components\Textarea::make('description')
                        ->required()
                        ->columnSpanFull(),
                    Components\TextInput::make('length')
                        ->label('Default Length (hours)')
                        ->numeric()
                        ->minValue(.25)
                        ->step(.25)
                        ->default(120)
                        ->formatStateUsing(fn ($state) => $state / 60)
                        ->dehydrateStateUsing(fn ($state) => $state * 60),
                    Components\TextInput::make('num_spots')
                        ->label('Default Number of People')
                        ->numeric()
                        ->default(1)
                        ->minValue(1),
                ])
                ->mutateFormDataUsing(fn ($data) => array_merge($data, [
                    'team_id' => $this->record->id,
                ]))
                ->after(fn () => $this->js('window.location.reload()')),
        ];
    }
}
