<?php

namespace Database\Factories;

use App\Enums\PageContentEnum;
use App\Models\Event;
use App\Models\PageContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageContent>
 */
class PageContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory()->active()->create()->id,
            'page' => PageContentEnum::AppDashboard,
            'content' => fake()->paragraphs(3, true),
        ];
    }
}
