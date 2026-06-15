<?php

declare(strict_types=1);

namespace Tests\Feature\Api\StripeWebhook;

use App\Http\Middleware\StripeWebhookMiddleware;
use App\Models\Ticketing\Cart;
use App\Models\Ticketing\Order;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use App\Services\CartService;
use App\Services\StripeService;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Checkout\Session;
use Stripe\Event;
use Tests\ApiRouteTestCase;

class CheckoutSessionCompletedTest extends ApiRouteTestCase
{
    public string $routeName = 'api.stripe-webhook';

    public bool $seed = true;

    public Session $session;

    public Event $event;

    /** @var array<mixed> */
    public array $eventData;

    protected function setUp(): void
    {
        parent::setUp();

        auth()->login(User::firstOrFail());

        $content = file_get_contents(storage_path('testing/stripe_checkout_completed_event.json'));

        $this->assertNotFalse($content);
        $this->eventData = json_decode($content, true);

        $this->event = Event::constructFrom($this->eventData);
        $this->eventData['event'] = $this->event;
        /** @phpstan-ignore-next-line */
        $this->session = $this->event->data->object;

        /** @var CartService $cartService */
        $cartService = app()->make(CartService::class);

        $ticketType = TicketType::query()->available()->firstOrFail();

        $cart = $cartService->createCartAndItems([
            [
                'id' => $ticketType->id,
                'quantity' => 1,
            ],
        ]);

        $cart->setStripeCheckoutIdAndSave($this->session->id);

        $this->partialMock(StripeService::class, function (MockInterface $mock) {
            /** @var Expectation $receive */
            $receive = $mock->shouldReceive('getCheckoutSession');
            $receive->andReturn($this->session);

            /** @var Expectation $updateMetadata */
            $updateMetadata = $mock->shouldReceive('updateMetadata');
            $updateMetadata->andReturn($this->session);
        });

        // Override middleware with test version to add event to request without all the checks
        $this->app->instance(StripeWebhookMiddleware::class, new StripeWebhookTestMiddleware($this->event));

        ini_set('error_log', '/dev/null');
    }

    #[Test]
    public function stripe_webhook_call_without_cart_returns_error(): void
    {
        $user = User::firstOrFail();
        $carts = Cart::where('user_id', $user->id)->get();

        $this->assertCount(1, $carts);

        $carts->firstOrFail()->deleteQuietly();

        $response = $this->actingAs($user)->postJson($this->endpoint, $this->eventData);

        $response->assertStatus(204);
    }

    #[Test]
    public function stripe_webhook_call_with_cart_returns_success(): void
    {
        $user = User::firstOrFail();
        $purchasedTicketCount = $user->PurchasedTickets()->count();

        $this->assertEquals(0, $purchasedTicketCount);

        $response = $this->actingAs($user)->postJson($this->endpoint, $this->eventData);

        $response->assertStatus(200);

        $this->assertEquals(1, User::firstOrFail()->PurchasedTickets()->count());
    }

    #[Test]
    public function stripe_webhook_call_with_incorrect_checkout_id_returns_error(): void
    {
        $user = User::firstOrFail();
        /** @var Cart $cart */
        $cart = $user->activeCart;
        $cart->stripe_checkout_id = 'wrong';
        $cart->saveQuietly();

        $response = $this->actingAs($user)->postJson($this->endpoint, $this->eventData);

        $response->assertStatus(204);
    }

    #[Test]
    public function stripe_webhook_call_with_incomplete_session_returns_error(): void
    {
        $user = User::firstOrFail();

        $this->session->status = 'incomplete';

        $response = $this->actingAs($user)->postJson($this->endpoint, $this->eventData);

        $response->assertStatus(204);
    }

    #[Test]
    public function stripe_webhook_call_with_unpaid_session_returns_error(): void
    {
        $user = User::firstOrFail();

        $this->session->payment_status = 'unpaid';

        $response = $this->actingAs($user)->postJson($this->endpoint, $this->eventData);

        $response->assertStatus(204);
    }

    #[Test]
    public function stripe_webhook_call_with_existing_order_returns_error(): void
    {
        $user = User::firstOrFail();

        Order::create([
            'user_email' => $user->email,
            'user_id' => $user->id,
            'event_id' => 1,
            'cart_id' => 1,
            'amount_subtotal' => 1,
            'amount_total' => 1,
            'amount_tax' => 1,
            'amount_fees' => 1,
            'quantity' => 1,
            'stripe_checkout_id' => $this->session->id,
            'ticket_data' => [],
        ]);

        $response = $this->actingAs($user)->postJson($this->endpoint, $this->eventData);

        $response->assertStatus(204);
    }
}
