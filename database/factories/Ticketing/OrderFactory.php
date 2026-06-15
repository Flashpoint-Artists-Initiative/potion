<?php

namespace Database\Factories\Ticketing;

use App\Models\Event;
use App\Models\Ticketing\Cart;
use App\Models\Ticketing\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_email' => fake()->email(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'cart_id' => Cart::factory(),
            'amount_subtotal' => fake()->randomNumber(3),
            'amount_total' => fake()->randomNumber(4, true),
            'amount_tax' => fake()->randomNumber(3),
            'amount_fees' => fake()->randomNumber(2),
            'quantity' => fake()->numberBetween(1, 4),
            'stripe_checkout_id' => fake()->regexify('cs_test_[A-Z0-9a-z]{58}_fake'),
            'ticket_data' => [],
        ];
    }
}
