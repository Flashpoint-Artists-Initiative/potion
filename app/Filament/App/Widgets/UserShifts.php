<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Volunteering\Shift;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserShifts extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Your Shifts')
            ->paginated(false)
            ->query(
                Shift::query()
                    ->whereHas('volunteers', function ($query) {
                        $query->where('user_id', Auth::user()->id ?? 0);
                    })
                    ->with(['shiftType', 'team'])
                    ->orderBy('start_offset'),
            )
            ->columns([
                TextColumn::make('team.name')
                    ->label('Team')
                    ->sortable(),
                TextColumn::make('shiftType.title')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('startCarbon')
                    ->label('Start Time')
                    ->dateTime('D, m/j g:ia T', 'America/New_York')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('start_offset', $direction)),
                TextColumn::make('endCarbon')
                    ->label('End Time')
                    ->dateTime('D, m/j g:ia T', 'America/New_York'),
                TextColumn::make('lengthInHours')
                    ->label('Duration (Hours)')
                    ->sortable(),
                TextColumn::make('volunteers_count')
                    ->label('Signed Up')
                    ->counts('volunteers')
                    ->formatStateUsing(fn(int $state, ?Shift $record) => sprintf('%d/%d', $state, $record->num_spots ?? 0)),
            ])
            ->actions([
                Action::make('cancel')
                    ->button()
                    ->action(function (Shift $record) {
                        $user = Auth::user();
                        if (! $user) {
                            return;
                        }

                        $user->shifts()->detach($record->id);
                        Notification::make()
                            ->title('You have removed a shift from your schedule.')
                            ->success()
                            ->send();
                    })
                    ->label('Cancel')
            ]);
    }

    public static function canView(): bool
    {
        return Auth::user() && Auth::user()->shifts()->count() > 0;
    }
}
