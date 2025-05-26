<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Ticketing\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use NumberFormatter;

class RevenueWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue', $this->getTotal('amount_total'))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Total Profit', $this->getTotal('amount_subtotal'))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Tickets Sold', Order::currentEvent()->notRefunded()->sum('quantity'))
                ->icon('heroicon-o-ticket')
                ->color('success'),
        ];
    }

    protected function getTotal(string $field): string
    {
        $total = Order::currentEvent()->notRefunded()->sum($field) / 100;

        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($total, 'USD') ?: '$0.00';
    }
}
