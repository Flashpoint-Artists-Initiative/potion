<?php

namespace Database\Factories\Ticketing;

use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservedTicket>
 */
class ReservedTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expiration_date' => fake()->dateTimeInInterval('+1 weeks', '+1 week'),
            'user_id' => User::factory()->create()->id,
            'ticket_type_id' => TicketType::factory()->create()->id,
            'email' => fake()->safeEmail(),
        ];
    }

    public function withEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => fake()->safeEmail(),
        ]);
    }

    public function expirationDateInPast(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiration_date' => fake()->dateTimeInInterval('-2 weeks', '-1 day'),
        ]);
    }
}
