<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Checkout;

use App\Models\Ticketing\Cart;
use App\Models\Ticketing\Order;
use App\Models\Ticketing\ReservedTicket;
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

class CheckoutCompleteTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'api.checkout.complete';

    public Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        auth()->login(User::firstOrFail());

        $content = file_get_contents(storage_path('testing/stripe_checkout_completed_event.json'));

        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $event = Event::constructFrom($data);
        /** @phpstan-ignore-next-line */
        $this->session = $event->data->object;

        /** @var CartService $cartService */
        $cartService = app()->make(CartService::class);

        /** @var User $user */
        $user = auth()->user();

        $ticketType = TicketType::query()->available()->firstOrFail();
        $reservedTicket = ReservedTicket::create([
            'ticket_type_id' => $ticketType->id,
            'user_id' => $user->id,
        ]);

        $cart = $cartService->createCartAndItems([
            [
                'id' => $ticketType->id,
                'quantity' => 1,
            ],
        ], [
            $reservedTicket->id,
        ]);

        $cart->setStripeCheckoutIdAndSave($this->session->id);

        $this->partialMock(StripeService::class, function (MockInterface $mock) {
            /** @var Expectation $getSession */
            $getSession = $mock->shouldReceive('getCheckoutSession');
            $getSession->andReturn($this->session);

            /** @var Expectation $updateMetadata */
            $updateMetadata = $mock->shouldReceive('updateMetadata');
            $updateMetadata->andReturn($this->session);
        });

    }

    #[Test]
    public function checkout_complete_call_not_logged_in_returns_error(): void
    {
        auth()->logout();

        $response = $this->post($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function checkout_complete_call_without_cart_returns_error(): void
    {
        $user = User::firstOrFail();
        $carts = Cart::where('user_id', $user->id)->get();

        $this->assertCount(1, $carts);

        $carts->firstOrFail()->deleteQuietly();

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function checkout_complete_call_with_cart_returns_success(): void
    {
        $user = User::firstOrFail();
        $purchasedTicketCount = $user->PurchasedTickets()->count();

        $this->assertEquals(0, $purchasedTicketCount);

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(204);

        $this->assertEquals(2, $user->PurchasedTickets()->count());
    }

    #[Test]
    public function checkout_complete_call_with_incorrect_checkout_id_returns_error(): void
    {
        $user = User::firstOrFail();
        /** @var Cart $cart */
        $cart = $user->activeCart;
        $cart->stripe_checkout_id = 'wrong';
        $cart->saveQuietly();

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function checkout_complete_call_with_incomplete_session_returns_error(): void
    {
        $user = User::firstOrFail();

        $this->session->status = 'incomplete';

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function checkout_complete_call_with_unpaid_session_returns_error(): void
    {
        $user = User::firstOrFail();

        $this->session->payment_status = 'unpaid';

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function checkout_complete_call_with_existing_order_returns_error(): void
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

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(422);
    }
}
