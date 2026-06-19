<?php

namespace Database\Factories\Volunteering;

use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

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
            'length' => fake()->numberBetween(1, 8) * 30,
            'num_spots' => fake()->numberBetween(1, 8),
            'multiplier' => '1',
        ];
    }
}
