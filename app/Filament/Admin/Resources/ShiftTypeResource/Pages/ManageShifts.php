<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftTypeResource\Pages;

use App\Filament\Admin\Resources\ShiftTypeResource;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use Carbon\Carbon;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Guava\FilamentNestedResources\Concerns\NestedPage;
use Illuminate\Support\HtmlString;

class ManageShifts extends EditRecord
{
    use NestedPage;

    protected static string $resource = ShiftTypeResource::class;

    protected static ?string $breadcrumb = 'Manage Shifts';

    public function getTitle(): string
    {
        /** @var ShiftType $shiftType */
        $shiftType = $this->getRecord();

        return $shiftType->title . ' Shifts';
    }

    public function form(Form $form): Form
    {
        /** @var ShiftType $shiftType */
        $shiftType = $this->getRecord();
        $event = $shiftType->event;
        $startDate = $event->start_date;
        $endDate = $event->end_date;

        $defaultLength = $shiftType->length / 60;
        $defaultNumSpots = $shiftType->num_spots;

        $hoursPlural = str('hour')->plural($defaultLength);
        $peoplePlural = str('person')->plural($defaultNumSpots);

        return $form
            ->schema([
                Placeholder::make('info')
                    ->label('')
                    ->content(new HtmlString(<<<HTML
                        <div>
                            <p>
                                Create shifts for <strong>{$event->name}</strong> -> <strong>{$shiftType->team->name}</strong> -> <strong>{$shiftType->title}</strong>
                            </p>
                            <p>
                                <strong>{$shiftType->title}</strong> shifts default to <strong>{$defaultLength}</strong> {$hoursPlural} and <strong>{$defaultNumSpots}</strong> {$peoplePlural}.
                                Leave the matching fields below to use the defaults.
                            </p>
                        </div>
                    HTML))
                    ->columnSpanFull(),
                Repeater::make('shifts')
                    ->label('')
                    ->relationship('shifts')
                    ->schema([
                        Components\DateTimePicker::make('start_offset')
                            ->label('Start Time')
                            ->required()
                            ->seconds(false)
                            ->formatStateUsing(fn ($record, $state) => $record->startDatetime ?? $state)
                            ->dehydrateStateUsing(fn ($state) => $event->startDateCarbon->diffInMinutes(Carbon::parse($state)))
                            ->format('Y-m-d H:i:s'),
                        Components\TextInput::make('length_in_hours')
                            ->label('Length (hours)')
                            ->numeric()
                            ->minValue(.25)
                            ->step(.25)
                            ->default($defaultLength),
                        Components\TextInput::make('num_spots')
                            ->label('Number of People')
                            ->numeric()
                            ->default($defaultNumSpots)
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
                    ])
                    ->cloneable()
                    ->columns(4)
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->deleteAction(fn (Action $action) => $action
                        ->requiresConfirmation(function($component) {
                            /** @var Shift $shift */
                            $shift = $component->getRecord();
                            return $shift->volunteers_count > 0;
                        }),
                    )
                    ->itemLabel(function (ComponentContainer $container) {
                        /** @var Shift $shift */
                        $shift = $container->getRecord();

                        return sprintf('Current Signups: %s (%.1f%%)', $shift->volunteers_count ?? 0, $shift->percentFilled ?? 0);
                    })
                    ->addAction(fn (Action $action) => $action
                        ->label('Add Shift')
                        ->icon('heroicon-o-plus')
                        ->color('primary')
                    ),
            ]);
    }
}
