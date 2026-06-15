<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiRouteTestCase;

class ForgotPasswordTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'password.email';

    #[Test]
    public function forgot_password_call_with_valid_data_returns_a_successful_response(): void
    {
        $response = $this->postJson($this->endpoint, ['email' => 'regular@example.com']);

        $response->assertStatus(200);
    }

    #[Test]
    public function forgot_password_email_is_sent(): void
    {
        $email = 'regular@example.com';

        /** @var ArrayTransport */
        $emailTransport = app('mailer')->getSymfonyTransport();

        $this->assertCount(0, $emailTransport->messages(), 'Start with 0 messages sent');

        $response = $this->postJson($this->endpoint, ['email' => $email]);

        $response->assertStatus(200);

        $this->assertCount(1, $emailTransport->messages(), 'Now one message is sent');

        $sentEmail = $emailTransport->messages()->pop();

        $this->assertEquals($sentEmail->getOriginalMessage()->getTo()[0]->getAddress(), $email, 'Email was sent to the correct address');
    }

    #[Test]
    public function multiple_forgot_password_calls_too_fast_returns_an_error_response(): void
    {
        $this->postJson($this->endpoint, ['email' => 'regular@example.com']);
        $response = $this->postJson($this->endpoint, ['email' => 'regular@example.com']);

        $response->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['message', 'errors.email']))
            ->assertJsonPath('message', 'Please wait before retrying.');
    }

    #[Test]
    public function forgot_password_call_with_missing_data_returns_a_validation_error(): void
    {
        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['message', 'errors.email']));
    }

    #[Test]
    public function forgot_password_call_with_invalid_email_returns_a_validation_error(): void
    {
        $response = $this->postJson($this->endpoint, ['email' => 'invalid_email']);

        $response->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['message', 'errors.email']));
    }

    #[Test]
    public function forgot_password_call_without_matching_user_email_returns_an_error(): void
    {
        $user = User::where('email', 'not-a-user@example.com')->first();

        $this->assertNull($user);

        $response = $this->postJson($this->endpoint, ['email' => 'not-a-user@example.com']);

        $response->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['message', 'errors.email']));
    }

    #[Test]
    public function forgot_password_call_while_logged_in_returns_an_error(): void
    {
        $user = User::findOrFail(1);
        $response = $this->actingAs($user)->postJson($this->endpoint, ['email' => $user->email]);

        $response->assertStatus(400);
    }
}
