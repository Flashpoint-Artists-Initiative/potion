<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Volunteering\Shifts\Signups;

use App\Models\Event;
use App\Models\User;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiRouteTestCase;

class ShiftSignupSignupTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'api.shifts.signups.signup';

    public array $routeParams = ['shift' => 1];

    #[Test]
    public function shift_signup_call_returns_a_successful_response(): void
    {
        $user = User::factory()->create();
        $preSignup = Shift::findOrFail(1);
        $this->assertCount(0, $preSignup->volunteers);

        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(204);

        $postSignup = Shift::findOrFail(1);
        $this->assertCount(1, $postSignup->volunteers);
    }

    #[Test]
    public function shift_signup_call_for_inactive_team_returns_error(): void
    {
        $team = Team::where('active', false)->has('shifts')->firstOrFail();
        $shift = $team->shifts->firstOrFail();

        $this->addEndpointParams(['shift' => $shift->id]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(403);
    }

    #[Test]
    public function shift_signup_call_for_inactive_event_returns_error(): void
    {
        $event = Event::where('active', false)->has('teams.shifts')->firstOrFail();
        $team = $event->teams()->where('active', true)->has('shifts')->firstOrFail();
        $shift = $team->shifts->firstOrFail();

        $this->addEndpointParams(['shift' => $shift->id]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(403);
    }

    #[Test]
    public function shift_signup_call_not_logged_in_returns_error(): void
    {
        $response = $this->postJson($this->endpoint);

        $response->assertStatus(401);
    }

    #[Test]
    public function shift_signup_call_for_full_shift_returns_error(): void
    {
        $user = User::factory()->create();
        $shift = Shift::findOrFail(1);

        $fill = range(1, (int) $shift->num_spots); // Array of user_ids to fill the shift
        $shift->volunteers()->sync($fill);

        $shift->refresh();

        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(422);
    }

    #[Test]
    public function shift_signup_call_for_double_signup_returns_error(): void
    {
        $user = User::factory()->create();
        $shift = Shift::findOrFail(1);

        $shift->volunteers()->attach($user);

        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(422);
    }

    #[Test]
    public function shift_signup_call_for_overlapping_shifts_returns_error(): void
    {
        $user = User::factory()->create();
        $shift = Shift::findOrFail(1);
        $secondShift = $shift->replicate();
        unset($secondShift->volunteers_count);
        $secondShift->save();

        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(204);

        $this->addEndpointParams(['shift' => $secondShift->id]);

        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(422);
    }
}
