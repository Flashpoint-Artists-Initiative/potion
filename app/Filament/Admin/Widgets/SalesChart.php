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

    protected ?string $heading = 'Tickets Sold';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

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
            if ($this->pageFilters && array_key_exists('startDate', $this->pageFilters) && $this->pageFilters['startDate']) {
                $startDate = new Carbon($this->pageFilters['startDate']);
            }

            if ($this->pageFilters && array_key_exists('endDate', $this->pageFilters) && $this->pageFilters['endDate']) {
                $endDate = new Carbon($this->pageFilters['endDate']);
            }
        } elseif ($this->filter === 'hour') {
            $dateFormat = 'ga';
            $startDate = now()->subDays(1);
            $endDate = now();
        }

        if ($this->pageFilters && array_key_exists('event', $this->pageFilters) && $this->pageFilters['event'] === 'current') {
            $query = $query->where('event_id', Event::getCurrentEventId());
        }

        if ($this->pageFilters && array_key_exists('refunds', $this->pageFilters) && $this->pageFilters['refunds'] === 'no') {
            $query = $query->notRefunded();
        }

        $trend = Trend::query($query)
            ->between(
                start: $startDate,
                end: $endDate,
            );

        $data = match ($this->filter) {
            'hour' => $trend->perHour()->sum('quantity'),
            'day' => $trend->perDay()->sum('quantity'),
            default => $trend->perDay()->sum('quantity'),
        };

        return [
            'labels' => $data->map(fn (TrendValue $value) => Carbon::parse($value->date)->setTimezone('America/New_York')->format($dateFormat)),
            'datasets' => [
                [
                    'label' => 'Tickets Sold',
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
