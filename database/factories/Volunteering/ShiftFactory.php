<?php

namespace Database\Factories\Volunteering;

use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_type_id' => ShiftType::factory(),
            'start_offset' => fake()->numberBetween(0, 48) * 60,
            'length' => fake()->randomDigit() > 3 ? fake()->numberBetween(1, 8) * 30 : null,
            'num_spots' => fake()->randomDigit() > 3 ? fake()->numberBetween(3, 8) : null,
        ];
    }
}
