<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class UserVolunteerShifts extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'shifts';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

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
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('start_offset', $direction)),
                Columns\TextColumn::make('lengthInHours')
                    ->label(new HtmlString('Duration<br>(Hours)'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('length', $direction)),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => ShiftResource::getUrl('view', ['record' => $record->id])),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]);
    }
}
