<?php

namespace Database\Factories\Volunteering;

use App\Models\Volunteering\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class ShiftTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'length' => fake()->numberBetween(1, 8) * 30,
            'num_spots' => fake()->numberBetween(3, 8),
        ];
    }
}
