<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Widgets;

use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Carbon\Carbon;
use Dedoc\Scramble\Console\Commands\Components\Component;
use Error;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Guava\Calendar\Actions\CreateAction;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\CalendarResource;
use Guava\Calendar\Widgets\CalendarWidget;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ShiftCalendarWidget extends CalendarWidget
{
    protected string $calendarView = 'timeGridWeek';
    // protected bool $dateClickEnabled = true;
    protected bool $dateSelectEnabled = true;
    protected bool $eventClickEnabled = true;
    protected bool $eventDragEnabled = true;
    protected bool $eventResizeEnabled = true;

    public Team $record;

    public function getOptions(): array
    {
        return [
            // 'allDaySlot' => false,
            'date' => $this->record->event->start_date,
            'headerToolbar' => ['start' => 'title', 'center' => 'resourceTimeGridWeek,resourceTimelineWeek,timeGridWeek', 'end' => 'prev,next'],
            'firstDay' => $this->record->event->startDateCarbon->dayOfWeek,
            // 'highlightedDates' => [
            //     $this->record->event->start_date,
            //     $this->record->event->end_date,
            // ],
            'slotDuration' => '00:15:00',
            'slotLabelInterval' => '02:00:00',
            'pointer' => true,
            'duration' => ['days' => $this->record->event->startDateCarbon->diffInDays($this->record->event->endDateCarbon) + 2],
        ];
    }

    public function authorize($ability, $arguments = []): bool
    {
        return true;
    }
    // public function onDateClick(array $info = []): void
    // {
    //     parent::onDateClick($info);
    // }

    // public function getDateSelectContextMenuActions(): array
    public function onDateSelect(array $info = []): void
    {
        // ->mutateFormDataUsing(fn($data) => array_merge($data, [
        //     'shift_type_id' => $this->record->id,
        // ]));

        if (Carbon::parse($info['startStr'])->isBefore($this->record->event->startDateCarbon)) {
            Notification::make()
                ->title('Error')
                ->body('You cannot create a shift before the event starts.')
                ->danger()
                ->send();
            return;
        }

        // if (Carbon::parse($info['endStr'])->isAfter($this->record->event->endDateCarbon)) {
        //     Notification::make()
        //         ->title('Error')
        //         ->body('You cannot create a shift after the event ends.')
        //         ->danger()
        //         ->send();
        //     return;
        // }

        $this->mountAction('createShift', $info);
    }

    public function createShiftAction(): CreateAction
    {
        return CreateAction::make('createShift')
            ->model(Shift::class)
            ->mountUsing(fn($arguments, $form) => $form->fill([
                'start_offset' => data_get($arguments, 'startStr'),
                'length_in_hours' => Carbon::parse(data_get($arguments, 'startStr'))
                    ->diffInMinutes(Carbon::parse(data_get($arguments, 'endStr'))) / 60,
            ]));
    }

    // public function getDateClickContextMenuActions(): array
    // {
    //     return [
    //         CreateAction::make('foo')
    //             ->model(Shift::class)
    //             ->mountUsing(fn ($arguments, $form) => $form->fill([
    //                 'start_offset' => data_get($arguments, 'startStr'),
    //                 'length' => Carbon::parse(data_get($arguments, 'startStr'))
    //                     ->diffInMinutes(Carbon::parse(data_get($arguments, 'endStr'))),
    //             ]))
    //             ->mutateFormDataUsing(fn($data) => array_merge($data, [
    //                 'shift_type_id' => $this->record->id,
    //             ]))
    //     ];
    // }

    public function onEventDrop(array $info = []): bool
    {
        parent::onEventDrop($info);
        $this->eventRecord->start_offset += $info['delta']['seconds'] / 60;
        try {
            $this->eventRecord->save();
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }

    public function onEventResize(array $info = []): bool
    {
        parent::onEventResize($info);
        $this->eventRecord->length += ($info['endDelta']['seconds'] / 60 / 60);
        try {
            $this->eventRecord->save();
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }

    public function getEvents(array $fetchInfo = []): Collection | array
    {
        // return $this->record->shifts;
        $shifts = Shift::all()->all();
        array_push($shifts, [
            'title' => 'Event Duration',
            'start' => $this->record->event->start_date,
            'end' => $this->record->event->endDateCarbon->addDay()->format('Y-m-d H:i:s'),
            'allDay' => true,
            'startEditable' => false,
            'durationEditable' => false,
            'classNames' => ['cursor-default'],
        ]);

        return $shifts;
    }

    public function getResources(): Collection|array
    {
        return $this->record->shiftTypes;
    }

    public function getEventClickContextMenuActions(): array
    {
        return [
            $this->viewAction(),
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    // public function getEventClickContextMenuActions(): array
    // {
    //     return [
    //         ViewAction::make('viewEvent')
    //             ->model(Shift::class)
    //     ];
    // }

    public function getSchema(?string $model = null): ?array
    {
        /** @var Team $team */
        $team = $this->record;
        /** @var Shift $shift */
        $shift = $this->eventRecord;

        $event = $team->event;
        $startDate = $event->start_date;
        $endDate = $event->end_date;

        // $defaultLength = $shift->shiftType->length / 60;
        // $defaultNumSpots = $shift->shiftType->num_spots;

        return [
            Components\Placeholder::make('info')
                ->label('Filled Slots')
                ->content(fn (?Shift $record) => $record?->volunteers_count)
                ->visible(fn (?Shift $record) => $record?->volunteers_count !== null),
            Components\Select::make('shift_type_id')
                ->label('Shift Type')
                ->relationship('team.shiftTypes', 'title')
                ->required()
                ->searchable()
                ->preload()
                // ->default($shift->shift_type_id)
                ->reactive()
                ->afterStateUpdated(function ($state, $set, $operation) {
                    $shiftType = ShiftType::where('id', $state)->firstOrFail();
                    if ($operation == 'createShift') {
                        $set('num_spots', $shiftType->num_spots);
                        // $set('length_in_hours', $shiftType->length / 60);
                    }
                }),
            Components\DateTimePicker::make('start_offset')
                ->label('Start Time')
                ->required()
                ->seconds(false)
                ->minDate($startDate)
                ->maxDate($endDate)
                ->formatStateUsing(fn($state, $record) => $record->startDatetime ?? $state)
                ->dehydrateStateUsing(fn($state) => $event->startDateCarbon->diffInMinutes(Carbon::parse($state)))
                ->format('Y-m-d H:i:s'),
            Components\TextInput::make('length_in_hours')
                ->label('Length (hours)')
                ->numeric()
                ->minValue(.25)
                ->step(.25),
                // ->placeholder((string) $defaultLength)
                // length is stored in minutes, but we want to show it in hours
                // If the length is the default, return null so the placeholder is used
                // ->formatStateUsing(fn($state) => $state / 60),
                // ->dehydrateStateUsing(function ($state) use ($defaultLength) {
                //     if ($state == null || $state * 60 == $defaultLength) {
                //         return null;
                //     }

                //     return $state * 60;
                // }),
            Components\TextInput::make('num_spots')
                ->label('Number of People')
                ->numeric()
                // ->placeholder((string) $defaultNumSpots)
                // use getRawOriginal to ignore the default value
                // ->formatStateUsing(fn(?Shift $record) => $record?->getRawOriginal('num_spots'))
                // ->dehydrateStateUsing(function ($state) use ($defaultNumSpots) {
                //     if ($state == null || $state == $defaultNumSpots) {
                //         return null;
                //     }

                //     return $state;
                // })
                ->minValue(1),
            Components\Select::make('multiplier')
                ->label('Multiplier')
                ->options([
                    '1' => '1x',
                    '1.5' => '1.5x',
                    '2' => '2x',
                ])
                ->selectablePlaceholder(false)
                ->default('1'),
        ];
    }

    public function getHeaderActionsssss(): array
    {
        return [
            // ActionGroup::make([
            Action::make('view')
                ->label('View')
                // ->url($this->record->url())
                ->action(fn() => $this->setOption('slotLabelInterval', '00:15:00'))
                ->icon('heroicon-o-eye')
                ->color('primary'),
            Action::make('edit')
                ->label('Edit')
                // ->url($this->record->editUrl())
                // ->action(fn () => dd($this->getEventsJs()))
                ->icon('heroicon-o-pencil')
                ->color('secondary'),
            Action::make('delete')
                ->label('Delete')
                ->action(fn() => $this->record->delete())
                ->icon('heroicon-o-trash')
                ->color('danger'),
            // ]),
        ];
    }
}
