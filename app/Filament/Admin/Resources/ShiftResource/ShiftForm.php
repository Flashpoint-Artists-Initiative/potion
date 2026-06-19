<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource;

use App\Forms\Components\VolunteerStartTimeField;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class ShiftForm
{
    public static function configure(
        Schema $schema,
        ShiftFormContext $context,
        ?Team $team = null,
        ?ShiftType $shiftType = null,
    ): Schema {
        $event = self::resolveEvent($team, $shiftType);

        return $schema
            ->extraAttributes([])
            ->schema([
                Grid::make()
                    ->schema([
                        ...self::shiftTypeField($context, $team, $shiftType),
                        ...self::startTimeField($context, $event),
                        Components\TextInput::make('length_in_hours')
                            ->label($context === ShiftFormContext::Calendar
                                ? 'Length (hours)'
                                : 'Length (in hours)')
                            ->required()
                            ->numeric()
                            ->minValue($context === ShiftFormContext::Calendar ? .25 : 0)
                            ->maxValue($context === ShiftFormContext::TeamCreate || $context === ShiftFormContext::ShiftTypeCreate ? 24 : null)
                            ->step(.25)
                            ->default($context === ShiftFormContext::TeamCreate || $context === ShiftFormContext::ShiftTypeCreate ? 1 : null),
                        Components\TextInput::make('num_spots')
                            ->label($context === ShiftFormContext::Calendar
                                ? 'Number of People'
                                : 'Number of Spots')
                            ->required()
                            ->numeric()
                            ->integer($context === ShiftFormContext::Calendar)
                            ->minValue(1)
                            ->default($context === ShiftFormContext::TeamCreate || $context === ShiftFormContext::ShiftTypeCreate ? 1 : null),
                        Components\Select::make('multiplier')
                            ->label('Multiplier')
                            ->required()
                            ->default('1')
                            ->selectablePlaceholder(false)
                            ->options(self::multiplierOptions($context))
                            ->helperText(self::multiplierHelperText($context)),
                    ]),
                ...self::calendarEditExtras($context),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function shiftTypeField(ShiftFormContext $context, ?Team $team, ?ShiftType $shiftType): array
    {
        if ($context === ShiftFormContext::Calendar && $team !== null) {
            return [
                Components\Select::make('shift_type_id')
                    ->label('Shift Type')
                    ->options(fn () => ShiftType::query()
                        ->where('team_id', $team->id)
                        ->orderBy('title')
                        ->pluck('title', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(condition: fn ($operation) => $operation === 'createShift')
                    ->afterStateUpdated(function ($state, $set, $operation): void {
                        if ($operation !== 'createShift') {
                            return;
                        }

                        $selectedShiftType = ShiftType::query()->where('id', $state)->firstOrFail();
                        $set('num_spots', $selectedShiftType->num_spots);
                    }),
            ];
        }

        if ($context === ShiftFormContext::TeamCreate && $team !== null) {
            return [
                Components\Select::make('shift_type_id')
                    ->label('Shift Type')
                    ->options(fn () => ShiftType::whereTeamId($team->id)->pluck('title', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $set): void {
                        $selectedShiftType = ShiftType::where('id', $state)->firstOrFail();
                        $set('num_spots', $selectedShiftType->num_spots);
                        $set('length_in_hours', $selectedShiftType->length / 60);
                    }),
            ];
        }

        if ($context === ShiftFormContext::ShiftTypeCreate) {
            return [
                Components\Placeholder::make('shift_type')
                    ->label('Shift Type')
                    ->content(fn (?Shift $record) => $record->shiftType->title ?? ($shiftType !== null ? $shiftType->title : 'Unknown')),
            ];
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    private static function startTimeField(ShiftFormContext $context, Event $event): array
    {
        if ($context === ShiftFormContext::Calendar) {
            return [
                VolunteerStartTimeField::make('start_offset')
                    ->event($event)
                    ->visible(fn (string $operation): bool => $operation === 'createShift'),
                Components\DateTimePicker::make('start_datetime')
                    ->label('Start Time')
                    ->required()
                    ->timezone($event->timezone)
                    ->seconds(false)
                    ->step(Event::VOLUNTEER_SLOT_MINUTES * 60)
                    ->format('Y-m-d H:i:s T')
                    ->visible(fn (string $operation): bool => $operation !== 'createShift'),
            ];
        }

        $field = Components\DateTimePicker::make('start_datetime')
            ->label('Start Time')
            ->required()
            ->timezone($event->timezone)
            ->seconds(false)
            ->step(Event::VOLUNTEER_SLOT_MINUTES * 60)
            ->format('Y-m-d H:i:s T')
            ->default($event->volunteerBaseDate->format('Y-m-d H:i:sp'));

        return [$field];
    }

    /**
     * @return array<int, mixed>
     */
    private static function calendarEditExtras(ShiftFormContext $context): array
    {
        if ($context !== ShiftFormContext::Calendar) {
            return [];
        }

        return [
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
        ];
    }

    /**
     * @return array<int|string, string>
     */
    private static function multiplierOptions(ShiftFormContext $context): array
    {
        if ($context === ShiftFormContext::Calendar) {
            return [
                '1' => '1x',
                '1.5' => '1.5x',
                '2' => '2x',
            ];
        }

        return [
            '0' => 'No credit',
            '1' => '1x',
            '1.5' => '1.5x',
            '2' => '2x',
        ];
    }

    private static function multiplierHelperText(ShiftFormContext $context): string
    {
        if ($context === ShiftFormContext::Calendar) {
            return 'Used to determine how many hours a volunteer will receive for this shift';
        }

        return 'Multiplier for the number of spots.';
    }

    private static function resolveEvent(?Team $team, ?ShiftType $shiftType): Event
    {
        if ($team !== null) {
            return $team->event;
        }

        if ($shiftType !== null) {
            return $shiftType->team->event;
        }

        throw new \InvalidArgumentException('ShiftForm requires a team or shift type context.');
    }
}
