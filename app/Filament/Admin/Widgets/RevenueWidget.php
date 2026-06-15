<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Event;
use App\Models\Ticketing\Cart;
use App\Models\Ticketing\Order;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use NumberFormatter;

class RevenueWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected Builder $query;

    protected function getStats(): array
    {
        $startDate = now()->subMonth();
        $endDate = now();

        if ($this->pageFilters && array_key_exists('startDate', $this->pageFilters) && $this->pageFilters['startDate']) {
            $startDate = new Carbon($this->pageFilters['startDate']);
        }

        if ($this->pageFilters && array_key_exists('endDate', $this->pageFilters) && $this->pageFilters['endDate']) {
            $endDate = new Carbon($this->pageFilters['endDate']);
        }

        $this->query = Order::query()->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->pageFilters && array_key_exists('event', $this->pageFilters) && $this->pageFilters['event'] === 'current') {
            $this->query = $this->query->where('event_id', Event::getCurrentEventId());
        }

        if ($this->pageFilters && array_key_exists('refunds', $this->pageFilters) && $this->pageFilters['refunds'] === 'no') {
            $this->query = $this->query->notRefunded();
        }

        $activeCarts = Cart::query()->doesntHave('order')->notExpired()->count();

        return [
            Stat::make('Total Revenue', $this->getTotal('amount_total'))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Total Profit', $this->getTotal('amount_subtotal'))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Tickets Sold', $this->query->sum('quantity'))
                ->icon('heroicon-o-ticket')
                ->color('success'),
            Stat::make('Active Carts', $activeCarts)
                ->icon('heroicon-o-shopping-cart')
                ->color('success'),
        ];
    }

    protected function getTotal(string $field): string
    {
        $total = $this->query->sum($field) / 100;

        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($total, 'USD') ?: '$0.00';
    }
}
