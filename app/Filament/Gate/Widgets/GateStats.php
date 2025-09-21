<?php

declare(strict_types=1);

namespace App\Filament\Gate\Widgets;

use App\Models\Ticketing\GateScan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GateStats extends BaseWidget
{
    protected function getStats(): array
    {
        $date = now('America/New_York');
        if ($date->hour <= 2) {
            $date->subDay();
        }

        $date->startOfDay();

        return [
            Stat::make('Total Scans', GateScan::count()),
            Stat::make('Today\'s Scans', GateScan::where('created_at', '>=', $date->copy()->addHours(2))
                ->where('created_at', '<=', $date->copy()->addDay()->addHours(2))
                ->count()),
            Stat::make('Remaining Users with Tickets', function (): int {
                return User::query()
                    ->whereHas('purchasedTickets', function ($query) {
                        $query->whereHas('ticketType', function ($q) {
                            $q->where('event_id', \App\Models\Event::getCurrentEventId());
                        });
                    })
                    ->whereDoesntHave('gateScans', function ($query) {
                        $query->where('event_id', \App\Models\Event::getCurrentEventId());
                    })
                    ->count();
            }),
        ];
    }
}
