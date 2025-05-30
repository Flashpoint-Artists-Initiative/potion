<?php

declare(strict_types=1);

namespace Tests\Unit\Ticketing;

use App\Models\Ticketing\Cart;
use App\Models\Ticketing\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public bool $seed = true;

    #[Test]
    public function user_relation(): void
    {
        $user = User::firstOrFail();
        $order = $this->createOrder($user);

        $this->assertEquals($order->user->id, $user->id);
    }

    #[Test]
    public function user_orders_relation(): void
    {
        $user = User::firstOrFail();
        $order = $this->createOrder($user);

        $orders = $user->orders()->get();

        $this->assertCount(1, $orders);

        $this->assertEquals($order->id, $orders->firstOrFail()->id);
    }

    #[Test]
    public function event_relation(): void
    {
        $user = User::firstOrFail();
        $order = $this->createOrder($user);

        $this->assertEquals(1, $order->event->id);
    }

    #[Test]
    public function cart_relation(): void
    {
        $user = User::firstOrFail();
        $cart = Cart::create(['user_id' => $user->id]);
        $order = $this->createOrder($user, $cart);

        $this->assertEquals($cart->id, $order->cart->id);
    }

    protected function createOrder(User $user, ?Cart $cart = null): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'event_id' => 1,
            'cart_id' => $cart->id ?? 1,
            'quantity' => 1,
            'user_email' => 'test@example.com',
            'amount_subtotal' => 100,
            'amount_total' => 110,
            'amount_tax' => 10,
            'amount_fees' => 5,
            'stripe_checkout_id' => 'abc',
            'ticket_data' => [],
        ]);
    }
}
