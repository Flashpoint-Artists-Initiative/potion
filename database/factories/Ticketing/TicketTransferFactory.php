<?php

namespace Database\Factories\Ticketing;

use App\Models\Ticketing\TicketTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketTransfer>
 */
class TicketTransferFactory extends Factory
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
            'recipient_email' => fake()->safeEmail(),
        ];
    }
}
