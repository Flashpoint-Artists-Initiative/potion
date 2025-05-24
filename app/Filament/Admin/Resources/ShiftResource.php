<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShiftResource\Pages;
use App\Filament\Admin\Resources\ShiftResource\RelationManagers;
use App\Models\Volunteering\Shift;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentNestedResources\Ancestor;
use Guava\FilamentNestedResources\Concerns\NestedResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\TeamResource;
use App\Filament\NestedResources\FarAncestor;
use App\Models\Volunteering\ShiftType;
use Carbon\Carbon;

class ShiftResource extends Resource
{
    use NestedResource;

    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $ownerRecord = $form->getExtraAttributes()['ownerRecord'] ?? null;

        return $form
            ->extraAttributes([]) // Reset the extra attributes
            ->schema([
                // Components\TextInput::make('start_offset')
                //     ->required()
                //     ->maxLength(255),
                Components\Select::make('shift_type_id')
                    ->label('Shift Type')
                    ->options(function ($record, $operation) use ($ownerRecord) {
                        if ($operation === 'create') {
                            return ShiftType::whereTeamId($ownerRecord->id)->pluck('title', 'id');
                        }
                        
                        return ShiftType::whereTeamId($record->team->id)->pluck('title', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    // ->default($shift->shift_type_id)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $operation) {
                        $shiftType = ShiftType::where('id', $state)->firstOrFail();
                        $set('num_spots', $shiftType->num_spots);
                        $set('length_in_hours', $shiftType->length / 60);
                    }),
                Components\DateTimePicker::make('start_offset')
                    ->label('Start Time')
                    ->required()
                    ->seconds(false)
                    ->default($ownerRecord?->event->startDateCarbon->format('Y-m-d H:i:s'))
                    ->formatStateUsing(fn ($state, $record) => $record->startDatetime ?? $state)
                    ->dehydrateStateUsing(fn ($record, $state) => $record->team->event->startDateCarbon->diffInMinutes(Carbon::parse($state)))
                    ->format('Y-m-d H:i:s'),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VolunteersRelationManager::class,
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
