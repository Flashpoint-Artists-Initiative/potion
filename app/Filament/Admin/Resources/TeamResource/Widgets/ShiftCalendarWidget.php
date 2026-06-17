<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Widgets;

use App\Filament\Admin\Resources\ShiftResource;
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
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'date' => $this->record->event->start_date,
            'headerToolbar' => ['start' => 'title', 'center' => 'resourceTimeGridDay,resourceTimelineWeek,timeGridWeek', 'end' => 'prev,next'],
            'firstDay' => $this->record->event->startDateCarbon->dayOfWeek,
            'slotDuration' => sprintf('00:%02d:00', self::SLOT_MINUTES),
            'pointer' => true,
            'duration' => ['days' => $this->record->event->startDateCarbon->diffInDays($this->record->event->endDateCarbon) + 1],
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
                ->start($this->record->event->start_date)
                ->end($this->record->event->endDateCarbon->addDays(1)->subMinute()->format('Y-m-d H:i:s'))
                ->allDay()
                ->startEditable(false)
                ->durationEditable(false)
                ->classNames(['cursor-default'])
        );
    }

    /**
     * @return Collection<int, ShiftType>|ShiftType[]
     */
    protected function getResources(): Collection|array|Builder
    {
        return $this->record->shiftTypes;
    }

    protected function onDateSelect(DateSelectInfo $info): void
    {
        if (! Auth::user()?->can('create', Shift::class)) {
            return;
        }

        $this->mountAction('createShift');
    }

    /**
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

                    $clickDate = Carbon::parse($dateStr, 'America/New_York');
                    $startOffset = $this->snapStartOffsetToSlot(
                        (int) round($this->record->event->volunteerBaseDate->diffInMinutes($clickDate))
                    );

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

    protected function snapStartOffsetToSlot(int $minutes): int
    {
        return (int) round($minutes / self::SLOT_MINUTES) * self::SLOT_MINUTES;
    }

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

    public function createShiftAction(): CreateAction
    {
        return $this->createAction(Shift::class, 'createShift')
            ->fillForm(function (DateSelectInfo $info): array {
                $data = [
                    'start_datetime' => $info->start->format('Y-m-d H:i:s'),
                    'length_in_hours' => $info->start->diffInMinutes($info->end) / 60,
                    'multiplier' => '1',
                ];

                if ($info->resource !== null) {
                    $shiftType = ShiftType::query()->find($info->resource->getId());

                    if ($shiftType !== null) {
                        $data['shift_type_id'] = $shiftType->id;
                        $data['num_spots'] = $shiftType->num_spots;
                    }
                }

                return $data;
            });
    }

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

    #[CalendarSchema(model: Shift::class)]
    protected function shiftSchema(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make()
                ->schema([
                    Components\Select::make('shift_type_id')
                        ->label('Shift Type')
                        ->relationship(
                            'team.shiftTypes',
                            'title',
                            fn ($query) => $query->where('team_id', $this->record->id)
                                ->orderBy('title')
                        )
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
                    Components\DateTimePicker::make('start_datetime')
                        ->label('Start Time')
                        ->timezone('America/New_York')
                        ->required()
                        ->seconds(false)
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
