<?php

namespace Database\Seeders;

use App\Enums\ArtProjectStatusEnum;
use App\Models\Event;
use App\Models\Grants\ArtProject;
use Illuminate\Database\Seeder;

class AddArtProjectsToEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Event $event): void
    {
        $event->settings['art']['voting_enabled'] = true;
        $event->save();

        ArtProject::factory()->for($event)->count(2)->create();
        ArtProject::factory()->for($event)->count(3)->state(['project_status' => ArtProjectStatusEnum::Approved])->create();
        ArtProject::factory()->for($event)->count(2)->state(['project_status' => ArtProjectStatusEnum::Denied])->create();

        $project = ArtProject::first();
    }
}
