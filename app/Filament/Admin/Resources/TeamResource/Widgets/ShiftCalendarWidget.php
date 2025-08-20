<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Widgets;

use App\Filament\Admin\Resources\ShiftResource\Pages\ViewShift;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction as ActionsCreateAction;
use Filament\Forms\Components;
use Filament\Forms\Components\Component;
use Guava\Calendar\Actions\CreateAction;
use Guava\Calendar\Actions\DeleteAction;
use Guava\Calendar\Actions\EditAction;
use Guava\Calendar\Widgets\CalendarWidget;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class ShiftCalendarWidget extends CalendarWidget
{
    protected string $calendarView = 'timeGridWeek';

    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected bool $eventResizeEnabled = true;

    public Team $record;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'date' => $this->record->event->start_date,
            'headerToolbar' => ['start' => 'title', 'center' => 'resourceTimeGridDay,resourceTimelineWeek,timeGridWeek', 'end' => 'prev,next'],
            'firstDay' => $this->record->event->startDateCarbon->dayOfWeek,
            'slotDuration' => '00:15:00',
            'pointer' => true,
            'duration' => ['days' => $this->record->event->startDateCarbon->diffInDays($this->record->event->endDateCarbon) + 1], // +1 to include the end date
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
     * @param array{
     *  start: string,
     *  startStr: string,
     *  end: string,
     *  endStr: string
     * } $fetchInfo
     *
     * @phpstan-ignore-next-line parameter.defaultValue
     */
    public function getEvents(array $fetchInfo = []): Collection|array
    {
        /** @var Collection<mixed> $events */
        $events = collect($this->record->shifts);
        $events->push([
            'title' => 'Event Duration',
            'start' => $this->record->event->start_date,
            'end' => $this->record->event->endDateCarbon->addDays(1)->subMinute()->format('Y-m-d H:i:s'),
            'allDay' => true,
            'startEditable' => false,
            'durationEditable' => false,
            'classNames' => ['cursor-default'],
        ]);
        $events->push([
            'title' => 'Event Start',
            // 'start' => '2025-10-26 01:00:00 -04:00',
            // 'end' => '2025-10-26 03:00:00 -04:00',
            'start' => Carbon::parse('2025-10-26 01:00:00', 'America/New_York'),
            'end' => Carbon::parse('2025-10-26 03:00:00', 'America/New_York'),
            'startEditable' => false,
            'durationEditable' => false,
            'classNames' => ['cursor-default'],
        ]);

        return $events;
    }

    /**
     * @return Collection<ShiftType> | ShiftType[]
     */
    public function getResources(): Collection|array
    {
        return $this->record->shiftTypes;
    }

    /**
     * @param array{
     *  start: string,
     *  startStr: string,
     *  end: string,
     *  endStr: string,
     *  allDay: boolean,
     *  view: array<string,string>,
     *  resource?: array<string,string>
     * } $info
     *
     * @phpstan-ignore-next-line parameter.defaultValue
     */
    public function onDateSelect(array $info = []): void
    {
        if (! Auth::authenticate()->can('create', Shift::class)) {
            return;
        }

        $this->mountAction('createShift', $info);
    }

    /**
     * @return Action[]
     */
    public function getDateClickContextMenuActions(): array
    {
        return $this->record->shiftTypes->map(function (ShiftType $shiftType) {
            return Action::make('createClick-' . $shiftType->title)
                ->label('New ' . $shiftType->title . ' Shift')
                ->model(Shift::class)
                ->icon('heroicon-o-plus')
                ->action(function ($arguments) use ($shiftType) {
                    return Shift::create([
                        // Set start_offset here instead of start_datetime because no team is set on a new shift record
                        'start_offset' => $this->record->event->volunteerBaseDate->diffInMinutes(Carbon::parse(data_get($arguments, 'dateStr'))),
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
     * @param array{
     *  event: array<string,string|bool|array<mixed>>,
     *  oldEvent: array<string,string|bool|array<mixed>>,
     *  resource: array<string,string|bool|array<mixed>>,
     *  oldResource: array<string,string|bool|array<mixed>>,
     *  delta: array{
     *   years: int,
     *   months: int,
     *   days: int,
     *   seconds: int,
     *  },
     *  view: array<string,string>
     * } $info
     *
     * @phpstan-ignore-next-line parameter.defaultValue
     */
    public function onEventDrop(array $info = []): bool
    {
        // To resolve eventRecord
        parent::onEventDrop($info);

        if (data_get($info, 'delta.seconds') == 0) {
            // No change, so just return
            return false;
        }

        /** @var Shift $shift */
        $shift = $this->eventRecord;

        if ($shift->volunteers_count > 0) {
            $info['action'] = 'doEventDrop';
            $this->mountAction('confirmEdit', $info);

            return false;
        } else {
            return $this->doEventDrop($info);
        }
    }

    /**
     * @param  array<mixed>  $info  Same as onEventDrop
     */
    protected function doEventDrop(array $info): bool
    {
        // To resolve eventRecord
        parent::onEventDrop($info);

        /** @var Shift $shift */
        $shift = $this->eventRecord;
        $shift->start_offset += data_get($info, 'delta.seconds') / 60;

        try {
            $shift->save();
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param array{
     *  event: array<string,string|bool|array<mixed>>,
     *  oldEvent: array<string,string|bool|array<mixed>>,
     *  endDelta: array{
     *   years: int,
     *   months: int,
     *   days: int,
     *   seconds: int,
     *  },
     *  view: array<string,string>
     * } $info
     *
     * @phpstan-ignore-next-line parameter.defaultValue
     */
    public function onEventResize(array $info = []): bool
    {

        // To resolve eventRecord
        parent::onEventDrop($info);

        /** @var Shift $shift */
        $shift = $this->eventRecord;

        if ($shift->volunteers_count > 0) {
            $info['action'] = 'doEventResize';
            $this->mountAction('confirmEdit', $info);

            return false;
        } else {
            return $this->doEventResize($info);
        }
    }

    /**
     * @param  array<mixed>  $info  Same as onEventResize
     */
    public function doEventResize(array $info): bool
    {
        // To resolve eventRecord
        parent::onEventDrop($info);

        /** @var Shift $shift */
        $shift = $this->eventRecord;
        $shift->length += ($info['endDelta']['seconds'] / 60);

        try {
            $shift->save();
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return Action[]
     */
    public function getEventClickContextMenuActions(): array
    {
        return [
            Action::make('view') // Has to be named 'view' because it checks ShiftPolicy
                ->label('Manage Shift')
                ->icon('heroicon-o-adjustments-horizontal')
                ->action(function ($livewire) {
                    redirect()->route(ViewShift::getRouteName(), [
                        'record' => $livewire->getEventRecord()->id,
                    ]);
                }),
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    /**
     * Used when creating a new shift by selecting an empty space on the calendar.
     */
    public function createShiftAction(): CreateAction
    {
        return CreateAction::make('createShift')
            ->model(Shift::class)
            ->mountUsing(fn ($arguments, $form) => $form->fill([
                'start_datetime' => data_get($arguments, 'startStr'),
                'length_in_hours' => Carbon::parse(data_get($arguments, 'startStr'))
                    ->diffInMinutes(Carbon::parse(data_get($arguments, 'endStr'))) / 60,
                'multiplier' => '1',
            ]));
    }

    /**
     * Used when editing a shift by dragging or resizing it.
     */
    public function confirmEditAction(): Action
    {
        return Action::make('confirmEdit')
            ->requiresConfirmation()
            ->modalHeading('Confirm Shift Change')
            ->modalSubmitActionLabel('Save and Notify')
            ->modalDescription(new HtmlString('This shift has volunteers signed up. Are you sure you want to change the start time?<br>
            Volunteers will be notified of this change. To save without notifying them, click the event and open the edit modal, make the changes, then click "Save Quietly".'))
            ->action(function ($arguments) {
                $this->{$arguments['action']}($arguments);
            })
            ->after(fn (self $livewire) => $livewire->refreshRecords());
    }

    /**
     * Used when editing a shift via the edit modal.
     */
    public function editAction(): Action
    {
        // In order to resolve the form data, we have to use mutateFormDataUsing
        // Then use makeModalSubmitAction to submit.  Creating a new action
        // like in deleteAction() does not work.
        return parent::editAction()
            ->modalHeading(fn (Shift $record) => sprintf(
                'Edit Shift #%d (%d %s)',
                $record->id,
                $record->volunteers_count,
                str('signup')->plural($record->volunteers_count)
            ))
            ->modalSubmitActionLabel(fn ($record) => $record->volunteers_count > 0 ? 'Save and Notify' : 'Save')
            ->mutateFormDataUsing(function (array $data, array $arguments, Shift $record) {
                if ($arguments['quietly'] ?? false) {
                    $record->dontNotifyVolunteers();
                }

                return $data;
            })
            ->extraModalFooterActions(fn (EditAction $action, $record) => $record->volunteers_count > 0 ? [
                $action->makeModalSubmitAction('saveQuietly', ['quietly' => true])
                    ->color('primary')
                    ->label('Save Quietly'),
            ] : []);
    }

    /**
     * Used when deleting a shift via the delete button.
     */
    public function deleteAction(): Action
    {
        return parent::deleteAction()
            // Add more description if there are volunteers signed up
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
            ->modalSubmitActionLabel(fn ($record) => $record->volunteers_count > 0 ? 'Delete and Notify' : 'Delete')
            ->extraModalFooterActions(fn ($record) => $record->volunteers_count > 0 ? [
                DeleteAction::make('deleteQuietly')
                    ->label('Delete Quietly')
                    ->modalHeading(null)
                    ->successNotificationTitle('Deleted quietly')
                    ->using(fn (Shift $record) => $record->dontNotifyVolunteers()->delete())
                    ->requiresConfirmation(false),
            ] : []);
    }

    /**
     * @return Component[]
     */
    public function getSchema(?string $model = null): ?array
    {
        /** @var Team $team */
        $team = $this->record;

        $event = $team->event;

        return [
            Components\Grid::make()
                ->schema([
                    Components\Select::make('shift_type_id')
                        ->label('Shift Type')
                        ->relationship('team.shiftTypes', 'title',
                            fn ($query) => $query->where('team_id', $this->record->id)
                                ->orderBy('title')
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        // ->default($shift->shift_type_id)
                        ->live(condition: fn ($operation) => $operation == 'createShift')
                        ->afterStateUpdated(function ($state, $set, $operation) {
                            $shiftType = ShiftType::where('id', $state)->firstOrFail();
                            if ($operation == 'createShift') {
                                $set('num_spots', $shiftType->num_spots);
                            }
                        }),
                    Components\DateTimePicker::make('start_datetime')
                        ->label('Start Time')
                        ->timezone('America/New_York')
                        ->required()
                        ->seconds(false)
                        // ->formatStateUsing(fn ($state, $record) => $record->startDatetime ?? $state)
                        // ->dehydrateStateUsing(fn ($state) => $event->volunteerBaseDate->diffInMinutes(Carbon::parse($state, 'America/New_York')))
                        ->format('Y-m-d H:i:s T'),
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
                ->content(new HtmlString(Blade::render('<x-notification-banner type="warning">{{$text}}</x-notification-banner>',
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
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getHeaderActions(): array
    {
        return [
            ActionsCreateAction::make('createShiftType')
                ->model(ShiftType::class)
                ->label('New Shift Type')
                ->form([
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
                // Have to reload after otherwise it doesn't show up in the on-click menu
                ->after(fn () => $this->js('window.location.reload()')),
        ];
    }
}
