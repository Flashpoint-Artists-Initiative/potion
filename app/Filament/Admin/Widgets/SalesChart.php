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

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Tickets Sold';

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected static ?int $sort = 2;

    public ?string $filter = 'day';

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Day',
            'hour' => 'Hour',

        ];
    }

    protected function getData(): array
    {
        $startDate = now()->subMonth();
        $endDate = now();
        $query = Order::query();
        $dateFormat = 'Y-m-d';


        if ($this->filter === 'day') {
            $dateFormat = 'F j';
            if ($this->filters && array_key_exists('startDate', $this->filters) && $this->filters['startDate']) {
                $startDate = new Carbon($this->filters['startDate']);
            }

            if ($this->filters && array_key_exists('endDate', $this->filters) && $this->filters['endDate']) {
                $endDate = new Carbon($this->filters['endDate']);
            }
        } elseif ($this->filter === 'hour') {
            $dateFormat = 'ga';
            $startDate = now()->subDays(1);
            $endDate = now();
        }

        if ($this->filters && array_key_exists('event', $this->filters) && $this->filters['event'] === 'current') {
            $query = $query->where('event_id', Event::getCurrentEventId());
        }

        if ($this->filters && array_key_exists('refunds', $this->filters) && $this->filters['refunds'] === 'no') {
            $query = $query->notRefunded();
        }

        $trend = Trend::query($query)
            ->between(
                start: $startDate,
                end: $endDate,
            );

        $data = match ($this->filter) {
            'hour' => $trend->perHour()->count(),
            'day' => $trend->perDay()->count(),
            default => $trend->perDay()->count(),
        };

        return [
            'labels' => $data->map(fn(TrendValue $value) => Carbon::parse($value->date)->format($dateFormat)),
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
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
