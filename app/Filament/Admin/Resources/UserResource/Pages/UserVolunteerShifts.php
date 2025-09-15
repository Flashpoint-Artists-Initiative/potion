<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions;

class UserVolunteerShifts extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'shifts';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $title = 'Volunteer Shifts';

    public static function getNavigationLabel(): string
    {
        return 'Volunteer Shifts';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('team.name')
                    ->label('Team')
                    ->sortable(),
                Columns\TextColumn::make('shiftType.title')
                    ->label('Position')
                    ->sortable(),
                Columns\TextColumn::make('startCarbon')
                    ->label('Start Time')
                    ->dateTime('D, m/j g:ia', 'America/New_York')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('start_offset', $direction)),
                Columns\TextColumn::make('lengthInHours')
                    ->label(new HtmlString('Duration<br>(Hours)'))
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('length', $direction)),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->url(fn ($record) => ShiftResource::getUrl('view', ['record' => $record->id])),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ]);
    }
}
