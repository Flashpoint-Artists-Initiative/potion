<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Me;

use App\Models\User;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiRouteTestCase;

class MeUpdateTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'api.me.update';

    public array $routeParams = ['user' => 1];

    #[Test]
    public function me_update_call_with_valid_data_returns_a_successful_response(): void
    {
        $user = User::firstOrFail();
        $data = $user->toArray();

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'legal_name' => fake()->name() . '1',
            'preferred_name' => fake()->userName(),
            'email' => fake()->safeEmail(),
            'birthday' => fake()->date(),
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->where('data.id', $data['id'])
            ->whereNot('data.legal_name', $data['legal_name'])
            ->whereNot('data.preferred_name', $data['preferred_name'])
            ->whereNot('data.email', $data['email'])
            ->whereNot('data.birthday', $data['birthday'])
        );
    }

    #[Test]
    public function me_update_call_with_invalid_data_returns_a_validation_error(): void
    {
        $user = User::firstOrFail();

        // Bad legal_name
        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'legal_name' => null,
            'email' => fake()->safeEmail(),
            'birthday' => fake()->date(),
        ]);

        $response->assertStatus(422);

        // Bad email
        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'legal_name' => fake()->name(),
            'email' => 'bad_email',
            'birthday' => fake()->date(),
        ]);

        $response->assertStatus(422);

        // Bad birthday
        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'legal_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'birthday' => 'This is an invalid date',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function me_update_call_not_logged_in_returns_error(): void
    {
        $response = $this->patchJson($this->endpoint, [
            'legal_name' => fake()->name(),
            'preferred_name' => fake()->userName(),
            'email' => fake()->safeEmail(),
            'birthday' => fake()->date(),
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function me_update_call_changing_email_resets_email_verification(): void
    {
        $user = User::whereNot('email_verified_at', null)->firstOrFail();

        $this->assertTrue($user->hasVerifiedEmail());

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'email' => 'new_email@example.com',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.id', $user->id);

        $user->refresh();

        $this->assertFalse($user->hasVerifiedEmail());
    }

    #[Test]
    public function me_update_call_changing_email_sends_new_verification_email(): void
    {
        $user = User::whereNot('email_verified_at', null)->firstOrFail();

        $this->assertTrue($user->hasVerifiedEmail());

        /** @var ArrayTransport */
        $emailTransport = app('mailer')->getSymfonyTransport();

        $this->assertCount(0, $emailTransport->messages(), 'Start with 0 messages sent');

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'email' => 'new_email@example.com',
        ]);

        $response->assertStatus(200);

        $this->assertCount(1, $emailTransport->messages(), 'Now one message is sent');

        $email = $emailTransport->messages()->pop();
        $user->refresh();

        $this->assertEquals($email->getOriginalMessage()->getTo()[0]->getAddress(), $user->email, 'Email was sent to the correct address');
    }

    #[Test]
    public function me_update_call_changing_password_hashes_correctly(): void
    {
        $user = User::firstOrFail();
        $newPassword = 'new_password';
        $oldHash = $user->password;

        $response = $this->actingAs($user)->patchJson($this->endpoint, [
            'password' => $newPassword,
        ]);

        $response->assertStatus(200);
        $user->refresh();

        $this->assertNotEquals($oldHash, $user->password);
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }
}
