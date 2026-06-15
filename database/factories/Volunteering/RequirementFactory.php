<?php

namespace Database\Factories\Volunteering;

use App\Models\Volunteering\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class RequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'icon' => fake()->word(),
            'description' => fake()->paragraph(),
        ];
    }
}
