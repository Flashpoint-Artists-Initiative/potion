<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Ticketing\Order;
use App\Models\User;
use App\Notifications\TaxEmailNotification;
use Illuminate\Console\Command;
use NumberFormatter;

class SendTaxEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'potion:send-tax-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send last month's sales total to accountant role users";

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $query = Order::query()->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]);

        $subtotal = $query->sum('amount_subtotal');
        $tax = $query->sum('amount_tax');
        $fees = $query->sum('amount_fees');

        $finalRevenue = $subtotal + $fees;
        $finalTax = $tax;

        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $finalRevenue = $formatter->formatCurrency($finalRevenue / 100, 'USD') ?: '$0.00';
        $finalTax = $formatter->formatCurrency($finalTax / 100, 'USD') ?: '$0.00';
        $month = now()->subMonth()->format('F Y');

        $accountants = User::role('accountant')->get();

        foreach ($accountants as $accountant) {
            $accountant->notify(new TaxEmailNotification($finalRevenue, $finalTax, $month));
        }
    }
}
