<?php

namespace Database\Factories\Ticketing;

use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasedTicket>
 */
class PurchasedTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'ticket_type_id' => TicketType::factory()->create()->id,
        ];
    }
}
