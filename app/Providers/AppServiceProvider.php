<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
            );
        });

        // Add localhost to the whitelisted stripe webhook ips when testing or local
        if ($this->app->isLocal() || $this->app->runningUnitTests()) {
            // @phpstan-ignore-next-line
            config(['services.stripe.webhook_ips.WEBHOOKS.100' => env('STRIPE_LOCAL_WEBHOOK_IP', '127.0.0.1')]);
        }

        Relation::enforceMorphMap([
            'cart' => \App\Models\Ticketing\Cart::class,
            'cartItem' => \App\Models\Ticketing\CartItem::class,
            'completedWaiver' => \App\Models\Ticketing\CompletedWaiver::class,
            'order' => \App\Models\Ticketing\Order::class,
            'purchasedTicket' => \App\Models\Ticketing\PurchasedTicket::class,
            'reservedTicket' => \App\Models\Ticketing\ReservedTicket::class,
            'ticketTransfer' => \App\Models\Ticketing\TicketTransfer::class,
            'ticketType' => \App\Models\Ticketing\TicketType::class,
            'waiver' => \App\Models\Ticketing\Waiver::class,
            'gateScan' => \App\Models\Ticketing\GateScan::class,

            'requirement' => \App\Models\Volunteering\Requirement::class,
            'shift' => \App\Models\Volunteering\Shift::class,
            'shiftType' => \App\Models\Volunteering\ShiftType::class,
            'team' => \App\Models\Volunteering\Team::class,

            'artProject' => \App\Models\Grants\ArtProject::class,

            'event' => \App\Models\Event::class,
            'user' => \App\Models\User::class,
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
