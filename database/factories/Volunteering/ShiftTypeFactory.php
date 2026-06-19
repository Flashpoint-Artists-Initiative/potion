<?php

namespace Database\Factories\Volunteering;

use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftType>
 */
class ShiftTypeFactory extends Factory
{
    protected $model = ShiftType::class;

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
            'location' => fake()->streetAddress(),
            'description' => fake()->paragraph(),
            'length' => fake()->numberBetween(1, 8) * 30,
            'num_spots' => fake()->numberBetween(3, 8),
        ];
    }
}
