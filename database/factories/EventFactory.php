<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Ticketing\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'location' => fake()->streetAddress(),
            'start_date' => fake()->dateTimeBetween('-1 week')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 week', '+2 weeks')->format('Y-m-d'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    public function offset(string $offset): static
    {
        return $this->state(function (array $attributes) use ($offset) {
            $start_date = fake()->dateTimeInInterval($offset, '+1 year');

            return [
                'start_date' => $start_date->format('Y-m-d'),
                'end_date' => $start_date->modify('+2 weeks')->format('Y-m-d'),
            ];
        });
    }

    public function withTicketTypes(): static
    {
        return $this->has(TicketType::factory()->withReservedTickets(), 'ticketTypes')
            ->has(TicketType::factory()->inactive(), 'ticketTypes')
            ->has(TicketType::factory()->inactive()->trashed(), 'ticketTypes')
            ->has(TicketType::factory()->zeroQuantity()->withPurchasedTickets(), 'ticketTypes')
            ->has(TicketType::factory()->free()->withReservedTickets(), 'ticketTypes')
            ->has(TicketType::factory()->onSaleInFuture(), 'ticketTypes')
            ->has(TicketType::factory()->onSaleInPast(), 'ticketTypes')
            ->has(TicketType::factory()->onSaleInPast()->trashed(), 'ticketTypes');
    }
}
