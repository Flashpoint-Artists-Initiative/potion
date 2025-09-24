<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Event;
use App\Models\Volunteering\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class VolunteerStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $teamIds = Team::query()->pluck('id')->toArray();

        $totalVolunteers = DB::query()
            ->selectRaw('COUNT(DISTINCT user_id) as total_volunteers')
            ->from('volunteer_data')
            ->whereIn('team_id', $teamIds)
            ->value('total_volunteers');

        $event = Event::with('teams.shifts')->findOrFail(Event::getCurrentEventId());
        $filledShifts = 0;
        $totalShifts = 0;
        $filledHours = 0;
        $totalHours = 0;

        foreach ($event->teams as $team) {
            foreach ($team->shifts as $shift) {
                if ($shift->multiplier == 0) {
                    continue;
                }

                $totalShifts += $shift->num_spots;
                $totalHours += $shift->length_in_hours * $shift->num_spots;

                $filledShifts += $shift->volunteers_count;
                $filledHours += $shift->volunteers_count * $shift->length_in_hours;
            }
        }

        
        return [
            Stat::make('Total Volunteers', $totalVolunteers)
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Total Shifts Filled', sprintf('%d / %d (%.1f%%)', $filledShifts, $totalShifts, $totalShifts > 0 ? ($filledShifts / $totalShifts * 100) : 0))
                ->icon('heroicon-o-calendar')
                ->color('primary'),
            Stat::make('Total Hours Filled', sprintf('%d / %d (%.1f%%)', $filledHours, $totalHours, $totalHours > 0 ? ($filledHours / $totalHours * 100) : 0))
                ->icon('heroicon-o-clock')
                ->color('primary'),
        ];
    }
}
