<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Checkout;

use App\Models\Ticketing\Cart;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiRouteTestCase;

class CheckoutDeleteTest extends ApiRouteTestCase
{
    public string $routeName = 'api.checkout.destroy';

    public bool $seed = true;

    #[Test]
    public function cart_delete_call_not_logged_in_returns_error(): void
    {
        $response = $this->delete($this->endpoint);

        $response->assertStatus(401);
    }

    #[Test]
    public function cart_delete_call_without_cart_returns_error(): void
    {
        $user = User::firstOrFail();
        $response = $this->actingAs($user)->delete($this->endpoint);

        $response->assertStatus(404);
    }

    #[Test]
    public function cart_delete_call_with_cart_returns_success(): void
    {
        $user = User::firstOrFail();
        Cart::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete($this->endpoint);

        $response->assertStatus(204);
    }

    #[Test]
    public function cart_delete_call_with_expired_cart_returns_error(): void
    {
        $user = User::firstOrFail();
        Cart::create(['user_id' => $user->id]);

        $this->travel(1)->hour();

        $response = $this->actingAs($user)->delete($this->endpoint);

        $response->assertStatus(404);
    }
}
