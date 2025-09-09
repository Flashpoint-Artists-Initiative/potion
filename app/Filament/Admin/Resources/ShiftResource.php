<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShiftResource\Pages;
use App\Filament\Admin\Resources\ShiftResource\RelationManagers;
use App\Filament\NestedResources\FarAncestor;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Guava\FilamentNestedResources\Ancestor;
use Guava\FilamentNestedResources\Concerns\NestedResource;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class ShiftResource extends Resource
{
    use NestedResource;

    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        // This form can be accessed from both the ShiftType and Team resources. Depending on which resource
        // it's accessed from, we show different fields.
        // Get the owner record from the page, if it exists.  See TeamResource/Pages/CreateShift
        /** @var ?Team $team */
        $team = $form->getExtraAttributes()['team'] ?? null;
        /** @var ?ShiftType $shiftType */
        $shiftType = $form->getExtraAttributes()['shiftType'] ?? null;

        if ($team) {
            $shiftTypeComponent = Components\Select::make('shift_type_id')
                ->label('Shift Type')
                ->options(fn () => ShiftType::whereTeamId($team->id)->pluck('title', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, $set) {
                    $shiftType = ShiftType::where('id', $state)->firstOrFail();
                    $set('num_spots', $shiftType->num_spots);
                    $set('length_in_hours', $shiftType->length / 60);
                });

            $startDefault = $team->event->volunteerBaseDate->format('Y-m-d H:i:sp');
        } else {
            $shiftTypeComponent = Components\Placeholder::make('shift_type')
                ->label('Shift Type')
                ->content(fn (?Shift $record) => $record->shiftType->title ?? $shiftType->title ?? 'Unknown');

            $startDefault = $shiftType?->team->event->volunteerBaseDate->format('Y-m-d H:i:sp') ?? null;
        }

        return $form
            ->extraAttributes([]) // Reset the extra attributes
            ->schema([
                $shiftTypeComponent,
                Components\DateTimePicker::make('start_datetime')
                    ->label('Start Time')
                    ->required()
                    ->timezone('America/New_York') // Don't set a timezone. Since everything runs off start_offset, this just breaks things
                    ->seconds(false)
                    ->default($startDefault)
                    ->step(15 * 60) // 15 minutes
                    ->format('Y-m-d H:i:s T'),
                Components\TextInput::make('length_in_hours')
                    ->label('Length (in hours)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(24)
                    ->step(0.25)
                    ->default(1),
                Components\TextInput::make('num_spots')
                    ->label('Number of Spots')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                Components\Select::make('multiplier')
                    ->label('Multiplier')
                    ->required()
                    ->default('1')
                    ->selectablePlaceholder(false)
                    ->options([
                        '0' => 'No credit',
                        '1' => '1x',
                        '1.5' => '1.5x',
                        '2' => '2x',
                    ])
                    ->helperText('Multiplier for the number of spots.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VolunteersRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditShift::route('/{record}/edit'),
            'view' => Pages\ViewShift::route('/{record}'),
        ];
    }

    public static function getAncestor(): ?Ancestor
    {
        return FarAncestor::make('shifts', 'team');
    }

    public static function getBreadcrumbRecordLabel(Shift $record): string
    {
        return $record->title . ' - ' . $record->start_carbon->format('D n/j, g:i A');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            TeamResource\Pages\ViewTeam::class,
            TeamResource\Pages\EditTeam::class,
            TeamResource\Pages\ShiftCalendar::class,
            TeamResource\Pages\ManageShiftTypes::class,
            TeamResource\Pages\ManageShifts::class,
        ]);
    }
}
