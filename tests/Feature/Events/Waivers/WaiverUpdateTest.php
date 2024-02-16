<?php

declare(strict_types=1);

namespace Tests\Feature\Events\Waivers;

use App\Enums\RolesEnum;
use App\Models\Ticketing\CompletedWaiver;
use App\Models\User;
use Database\Seeders\Testing\WaiverSeeder;
use Tests\ApiRouteTestCase;

class WaiverUpdateTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $seeder = WaiverSeeder::class;

    public string $routeName = 'api.events.waivers.update';

    public array $routeParams = ['event' => 1, 'waiver' => 1];

    public function test_waiver_update_call_with_valid_data_returns_a_successful_response(): void
    {
        $user = User::role(RolesEnum::Admin)->first();

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'title' => 'Test Waiver Update',
            'content' => 'test content update',
        ]);

        $response->assertStatus(200);
    }

    public function test_waiver_update_call_with_invalid_data_returns_a_validation_error(): void
    {
        $user = User::role(RolesEnum::Admin)->first();

        // Bad title
        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'title' => ['data'],
        ]);

        $response->assertStatus(422);

        //Bad content
        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'content' => null,
        ]);

        $response->assertStatus(422);

        //Bad minor_waiver
        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'minor_waiver' => 'sure',
        ]);

        $response->assertStatus(422);
    }

    public function test_waiver_update_call_without_permission_returns_error(): void
    {

        $user = User::doesntHave('roles')->first();

        $this->assertFalse($user->can('waivers.update'));

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'title' => 'Test Waiver Update',
            'content' => 'test content update',
        ]);

        $response->assertStatus(403);
    }

    public function test_waiver_update_call_not_logged_in_returns_error(): void
    {
        $response = $this->patchJson($this->endpoint, [
            'title' => 'Test Waiver Update',
            'content' => 'test content update',
        ]);

        $response->assertStatus(401);
    }

    public function test_waiver_update_call_with_completed_waivers_returns_error(): void
    {
        $user = User::role(RolesEnum::Admin)->first();

        CompletedWaiver::create(['user_id' => $user->id, 'waiver_id' => 1]);

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'title' => 'Test Waiver Update',
            'content' => 'test content update',
        ]);

        $response->assertStatus(403);
    }
}
