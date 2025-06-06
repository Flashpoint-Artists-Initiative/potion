<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Event;
use App\Models\Ticketing\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class OrdersChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Orders per Day';

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $startDate = now()->subMonth();
        $endDate = now();
        $query = Order::query();

        if ($this->filters && array_key_exists('startDate', $this->filters) && $this->filters['startDate']) {
            $startDate = new Carbon($this->filters['startDate']);
        }

        if ($this->filters && array_key_exists('endDate', $this->filters) && $this->filters['endDate']) {
            $endDate = new Carbon($this->filters['endDate']);
        }

        if ($this->filters && array_key_exists('event', $this->filters) && $this->filters['event'] === 'current') {
            $query = $query->where('event_id', Event::getCurrentEventId());
        }

        if ($this->filters && array_key_exists('refunds', $this->filters) && $this->filters['refunds'] === 'no') {
            $query = $query->notRefunded();
        }

        $data = Trend::query($query)
            ->between(
                start: $startDate,
                end: $endDate,
            )
            ->perDay()
            ->count();

        return [
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'fill' => 'start',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
