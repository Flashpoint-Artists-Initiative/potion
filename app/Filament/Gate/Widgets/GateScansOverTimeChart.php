<?php

declare(strict_types=1);

namespace App\Filament\Gate\Widgets;

use App\Models\Event;
use App\Models\Ticketing\GateScan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class GateScansOverTimeChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected static ?string $maxHeight = '300px';
    
    protected function getData(): array
    {
        $dateFormat = 'g:i a';

        $data = Trend::model(GateScan::class)
            ->between(now()->subHours(24), now()->addMinute())
            ->perHour()
            ->count();
        return [
            'datasets' => [
                [
                    'label' => 'Gate Scans',
                    'data' => $data->map(fn ($item) => $item->aggregate),
                    // 'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    // 'borderColor' => 'rgba(54, 162, 235, 1)',
                    // 'borderWidth' => 1,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => Carbon::parse($value->date)->setTimezone('America/New_York')->format($dateFormat)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
