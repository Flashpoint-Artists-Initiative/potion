<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Event;
use App\Models\Grants\ArtProject;
use App\Models\Ticketing\Cart;
use App\Models\Ticketing\CartItem;
use App\Models\Ticketing\CompletedWaiver;
use App\Models\Ticketing\GateScan;
use App\Models\Ticketing\Order;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketTransfer;
use App\Models\Ticketing\TicketType;
use App\Models\Ticketing\Waiver;
use App\Models\User;
use App\Models\Volunteering\Requirement;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Stripe\StripeClient;

/**
 * @property Application $app
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var mixed[]
     */
    public $bindings = [
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerStripeClient();
    }

    /**
     * Bootstrap any application services.
     *
     * @codeCoverageIgnore
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            if (! $this->app->isLocal()) {
                $rule = $rule->letters()->numbers()->uncompromised();
            }

            return $rule;
        });

        // Add localhost to the whitelisted stripe webhook ips when testing or local
        if ($this->app->isLocal() || $this->app->runningUnitTests()) {
            // @phpstan-ignore-next-line
            config(['services.stripe.webhook_ips.WEBHOOKS.100' => env('STRIPE_LOCAL_WEBHOOK_IP', '127.0.0.1')]);
        }

        Relation::enforceMorphMap([
            'cart' => Cart::class,
            'cartItem' => CartItem::class,
            'completedWaiver' => CompletedWaiver::class,
            'order' => Order::class,
            'purchasedTicket' => PurchasedTicket::class,
            'reservedTicket' => ReservedTicket::class,
            'ticketTransfer' => TicketTransfer::class,
            'ticketType' => TicketType::class,
            'waiver' => Waiver::class,
            'gateScan' => GateScan::class,

            'requirement' => Requirement::class,
            'shift' => Shift::class,
            'shiftType' => ShiftType::class,
            'team' => Team::class,

            'artProject' => ArtProject::class,

            'event' => Event::class,
            'user' => User::class,
        ]);
    }

    protected function registerStripeClient(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            $config = $app['config']->get('services.stripe');

            $secret = $config['secret'] ?? null;

            return new StripeClient($secret);
        });
    }
}
