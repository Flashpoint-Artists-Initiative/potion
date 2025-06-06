<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(4)
                    ->schema([
                        DatePicker::make('startDate'),
                        DatePicker::make('endDate'),
                        Select::make('event')
                            ->label('Event')
                            ->options([
                                'current' => 'Current Event',
                                'all' => 'All Events',
                            ])
                            ->default('current')
                            ->selectablePlaceholder(false),
                        Select::make('refunds')
                            ->label('Include Refunds')
                            ->options([
                                'yes' => 'Yes',
                                'no' => 'No',
                            ])
                            ->default('yes')
                            ->selectablePlaceholder(false),
                    ]),
            ]);
    }
}
