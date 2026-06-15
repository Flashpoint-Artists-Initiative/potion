<?php

namespace Database\Factories\Ticketing;

use App\Models\Ticketing\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expiration_date' => fake()->dateTimeThisCentury(now()->addDay()),
        ];
    }
}
