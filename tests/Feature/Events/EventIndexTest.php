<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Enums\RolesEnum;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\Testing\EventSeeder;
use Tests\ApiRouteTestCase;

class EventIndexTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $seeder = EventSeeder::class;

    public string $routeName = 'api.events.index';

    public function test_event_index_call_while_not_logged_in_returns_only_active_untrashed_events(): void
    {
        $event_count = Event::where('active', true)->withoutTrashed()->count();

        $response = $this->get($this->endpoint);

        $response->assertStatus(200);
        $this->assertEquals($event_count, $response->baseResponse->original->count());
    }

    public function test_event_index_call_with_permission_returns_pending_events(): void
    {
        $active_event_count = Event::where('active', true)->withoutTrashed()->count();
        $event_count = Event::withoutTrashed()->count();

        $this->assertGreaterThan($active_event_count, $event_count);

        $user = User::doesntHave('roles')->first();
        $user->givePermissionTo('events.viewPending');

        $response = $this->actingAs($user)->get($this->endpoint);
        $response->assertStatus(200);
        $this->assertEquals($event_count, $response->baseResponse->original->count());
    }

    public function test_event_index_call_with_permission_returns_trashed_events(): void
    {
        $this->buildEndpoint(params: ['with_trashed' => true]);

        $existing_event_count = Event::where('active', true)->count();
        $event_count = Event::where('active', true)->withTrashed()->count();

        $this->assertGreaterThan($existing_event_count, $event_count);

        $user = User::doesntHave('roles')->first();
        $user->givePermissionTo('events.viewDeleted');

        $response = $this->actingAs($user)->get($this->endpoint);
        $response->assertStatus(200);
        $this->assertEquals($event_count, $response->baseResponse->original->count());
    }

    public function test_event_index_call_without_permission_ignores_trashed_events(): void
    {
        $this->buildEndpoint(params: ['with_trashed' => true]);

        $existing_event_count = Event::where('active', true)->count();
        $event_count = Event::where('active', true)->withTrashed()->count();

        $this->assertGreaterThan($existing_event_count, $event_count);

        // No permission for events.viewDeleted
        $user = User::doesntHave('roles')->first();

        $response = $this->actingAs($user)->get($this->endpoint);
        $response->assertStatus(200);
        // Matches existing event count, not trashed
        $this->assertEquals($existing_event_count, $response->baseResponse->original->count());
    }

    public function test_event_index_call_with_only_trashed_returns_correct_events(): void
    {
        $this->buildEndpoint(params: ['only_trashed' => true]);

        $event_count = Event::where('active', true)->withTrashed()->count();
        $trashed_event_count = Event::where('active', true)->onlyTrashed()->count();

        $this->assertLessThan($event_count, $trashed_event_count);

        $user = User::doesntHave('roles')->first();
        $user->givePermissionTo('events.viewDeleted');

        $response = $this->actingAs($user)->get($this->endpoint);
        $response->assertStatus(200);
        $this->assertEquals($trashed_event_count, $response->baseResponse->original->count());
    }

    public function test_event_index_call_as_admin_returns_all_events(): void
    {
        $this->buildEndpoint(params: ['with_trashed' => true]);

        $event_count = Event::count();
        $all_event_count = Event::withTrashed()->count();

        $this->assertGreaterThan($event_count, $all_event_count);

        $user = User::role(RolesEnum::SuperAdmin)->first();

        $response = $this->actingAs($user)->get($this->endpoint);
        $response->assertStatus(200);
        $this->assertEquals($all_event_count, $response->baseResponse->original->count());
    }
}
