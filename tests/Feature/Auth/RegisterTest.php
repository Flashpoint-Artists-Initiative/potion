<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\ApiRouteTestCase;

class RegisterTest extends ApiRouteTestCase
{
    public string $routeName = 'register';

    public function test_registering_with_valid_data_returns_a_successful_response(): void
    {
        $response = $this->postJson($this->endpoint, [
            'legal_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'birthday' => '2000-01-01',
        ]);

        $response->assertStatus(201);
    }

    public function test_registering_without_valid_data_returns_validation_errors(): void
    {
        $response = $this->postJson($this->endpoint);

        $response->assertStatus(422)
            ->assertJson(function (AssertableJson $json) {
                return $json->hasAll(['message', 'errors.legal_name', 'errors.email', 'errors.password']);
            });
    }

    public function test_registering_with_existing_email_returns_validation_errors(): void
    {
        $this->seed();

        $response = $this->postJson($this->endpoint, [
            'legal_name' => 'Test User',
            'email' => 'regular@example.com',
            'password' => 'password',
            'birthday' => '2000-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJson(function (AssertableJson $json) {
                return $json->hasAll(['message', 'errors.email']);
            });
    }

    public function test_registering_when_logged_in_returns_error(): void
    {
        $this->seed();

        $user = User::find(1);

        $response = $this->actingAs($user)->postJson($this->endpoint, [
            'legal_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'birthday' => '2000-01-01',
        ]);

        $response->assertStatus(400);
    }
}
