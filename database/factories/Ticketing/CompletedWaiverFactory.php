<?php

namespace Database\Factories\Ticketing;

use App\Models\CompletedWaiver;
use App\Models\Ticketing\Waiver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompletedWaiver>
 */
class CompletedWaiverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'waiver_id' => Waiver::factory(),
            'user_id' => User::factory(),
        ];
    }
}
