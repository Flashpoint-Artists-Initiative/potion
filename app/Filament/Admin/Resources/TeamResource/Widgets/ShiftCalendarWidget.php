<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Widgets;

use App\Data\Volunteering\CalendarSelection;
use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\ShiftResource\ShiftForm;
use App\Filament\Admin\Resources\ShiftResource\ShiftFormContext;
use App\Filament\Concerns\PassesCalendarContextToFilamentActions;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use App\Services\ShiftSchedulingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction as ActionsCreateAction;
use Filament\Forms\Components;
use Filament\Schemas\Schema;
use Guava\Calendar\Attributes\CalendarSchema;
use Guava\Calendar\Enums\CalendarViewType;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ShiftCalendarWidget extends CalendarWidget
{
    use PassesCalendarContextToFilamentActions;

    public Team $record;

    protected ShiftSchedulingService $scheduling;

    protected CalendarViewType $calendarView = CalendarViewType::TimeGridWeek;

    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected bool $eventResizeEnabled = true;

    public function boot(ShiftSchedulingService $scheduling): void
    {
        $this->scheduling = $scheduling;
    }

    public function mount(): void
    {
        $this->record->load(['event', 'shiftTypes']);
    }

    protected function event(): Event
    {
        return $this->record->event;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'date' => $this->event()->start_date,
            'timeZone' => $this->event()->timezone,
            'headerToolbar' => ['start' => 'title', 'center' => 'resourceTimeGridDay,resourceTimelineWeek,timeGridWeek', 'end' => 'prev,next'],
            'firstDay' => $this->event()->startDateCarbon->dayOfWeek,
            'slotDuration' => sprintf('00:%02d:00', Event::VOLUNTEER_SLOT_MINUTES),
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
     * @return Collection<int, Shift|CalendarEvent>|Builder<Shift>|array<int, mixed>
     */
    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        $event = $this->event();
        $visibleStartOffset = $event->minutesFromVolunteerBase(
            $info->start->setTimezone($event->timezone)
        );
        $visibleEndOffset = $event->minutesFromVolunteerBase(
            $info->end->setTimezone($event->timezone)
        );

        /** @var Collection<int, Shift|CalendarEvent> $events */
        $events = collect(
            Shift::query()
                ->whereHas('shiftType', fn (Builder $query) => $query->where('team_id', $this->record->id))
                ->where('start_offset', '<', $visibleEndOffset)
                ->whereRaw('start_offset + length > ?', [$visibleStartOffset])
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
            return Action::make('createClick-' . $shiftType->id)
                ->label('New ' . $shiftType->title . ' Shift')
                ->model(Shift::class)
                ->icon('heroicon-o-plus')
                ->action(function (self $livewire) use ($shiftType): void {
                    $arguments = $livewire->getMountedAction()?->getArguments() ?? [];
                    $dateStr = data_get($arguments, 'data.dateStr');

                    if (! is_string($dateStr) || $dateStr === '') {
                        return;
                    }

                    $livewire->scheduling->createFromDateClick($shiftType, $dateStr);
                })
                ->after(fn (self $livewire) => $livewire->refreshRecords());
        })->all();
    }

    protected function onEventDrop(EventDropInfo $info, Model $event): bool
    {
        if (! $event instanceof Shift) {
            return false;
        }

        $deltaMinutes = $this->eventDropDeltaMinutes();

        if ($deltaMinutes === 0) {
            return false;
        }

        if ($event->volunteers_count > 0) {
            $this->mountAction('confirmMoveShift', [
                'shiftId' => $event->id,
                'deltaMinutes' => $deltaMinutes,
            ]);

            return false;
        }

        return $this->applyEventDrop($event, $deltaMinutes);
    }

    protected function applyEventDrop(Shift $shift, int $deltaMinutes): bool
    {
        return $this->scheduling->moveByMinutes($shift, $deltaMinutes);
    }

    protected function eventDropDeltaMinutes(): int
    {
        return intdiv((int) $this->getRawCalendarContextData('delta.seconds'), 60);
    }

    protected function onEventResize(EventResizeInfo $info, Model $event): bool
    {
        if (! $event instanceof Shift) {
            return false;
        }

        if ($event->volunteers_count > 0) {
            $this->mountAction('confirmResizeShift', [
                'shiftId' => $event->id,
                'deltaMinutes' => $this->eventResizeDeltaMinutes(),
            ]);

            return false;
        }

        return $this->applyEventResize($event, $this->eventResizeDeltaMinutes());
    }

    protected function applyEventResize(Shift $shift, int $deltaMinutes): bool
    {
        return $this->scheduling->resizeByMinutes($shift, $deltaMinutes);
    }

    protected function eventResizeDeltaMinutes(): int
    {
        return intdiv((int) $this->getRawCalendarContextData('endDelta.seconds'), 60);
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
            ->fillForm(function (DateSelectInfo $info, self $livewire): array {
                $raw = $livewire->getRawCalendarContextData();

                return CalendarSelection::fromWidgetContext(
                    is_array($raw) ? $raw : null,
                    $livewire->event(),
                )->toCreateFormDefaults();
            })
            ->using(fn (array $data, self $livewire): Shift => $livewire->scheduling->createFromFormData($livewire->record, $data));
    }

    public function confirmMoveShiftAction(): Action
    {
        return Action::make('confirmMoveShift')
            ->requiresConfirmation()
            ->modalHeading('Confirm Shift Change')
            ->modalSubmitActionLabel('Save and Notify')
            ->modalDescription('This shift has volunteers signed up. Are you sure you want to change the start time? Volunteers will be notified of this change. To save without notifying them, click the event and open the edit modal, make the changes, then click "Save Quietly".')
            ->action(function (self $livewire, array $arguments): void {
                $shift = Shift::query()->find($arguments['shiftId'] ?? null);

                if (! $shift instanceof Shift) {
                    return;
                }

                $livewire->applyEventDrop($shift, (int) ($arguments['deltaMinutes'] ?? 0));
            })
            ->after(fn (self $livewire) => $livewire->refreshRecords());
    }

    public function confirmResizeShiftAction(): Action
    {
        return Action::make('confirmResizeShift')
            ->requiresConfirmation()
            ->modalHeading('Confirm Shift Change')
            ->modalSubmitActionLabel('Save and Notify')
            ->modalDescription('This shift has volunteers signed up. Are you sure you want to change the shift length? Volunteers will be notified of this change. To save without notifying them, click the event and open the edit modal, make the changes, then click "Save Quietly".')
            ->action(function (self $livewire, array $arguments): void {
                $shift = Shift::query()->find($arguments['shiftId'] ?? null);

                if (! $shift instanceof Shift) {
                    return;
                }

                $livewire->applyEventResize($shift, (int) ($arguments['deltaMinutes'] ?? 0));
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
        return ShiftForm::configure($schema, ShiftFormContext::Calendar, $this->record);
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
                ->after(function (self $livewire): void {
                    $livewire->record->load('shiftTypes');
                    $livewire->refreshRecords();
                }),
        ];
    }
}
