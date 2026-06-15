<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiRouteTestCase;

class VerifyEmailTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'verification.send';

    #[Test]
    public function send_verification_email_endpoint_requires_being_logged_in(): void
    {
        $response = $this->postJson($this->endpoint);

        $response->assertStatus(401);
    }

    #[Test]
    public function verification_email_is_sent(): void
    {
        User::create([
            'legal_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user = User::firstOrFail();

        /** @var ArrayTransport */
        $emailTransport = app('mailer')->getSymfonyTransport();

        $this->assertCount(0, $emailTransport->messages(), 'Start with 0 messages sent');

        $response = $this->actingAs($user)->postJson($this->endpoint);

        $response->assertStatus(202);

        $this->assertCount(1, $emailTransport->messages(), 'Now one message is sent');

        $email = $emailTransport->messages()->pop();

        $this->assertEquals($email->getOriginalMessage()->getTo()[0]->getAddress(), $user->email, 'Email was sent to the correct address');
    }

    #[Test]
    public function email_verification_link_works(): void
    {
        $this->markTestSkipped('Requires working frontend to test');

        // @phpstan-ignore-next-line Remove when test nolonger skipped
        User::create([
            'legal_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user = User::firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());

        // Manually generate the verification URL
        $verificationEmail = new VerifyEmail;
        $url = $verificationEmail->toMail($user)->actionUrl;
        $parts = parse_url($url);

        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';

        $relativeUrl = "$path?$query";

        $response = $this->actingAs($user)->get($relativeUrl);

        $response->assertStatus(202);

        // Update the user model
        $user->refresh();

        $this->assertTrue($user->hasVerifiedEmail());

        // Calling endpoint when already verified fails
        $response = $this->actingAs($user)->postJson($this->endpoint);
        $response->assertStatus(400);
    }

    #[Test]
    public function email_verification_link_fails_the_second_time(): void
    {
        $this->markTestSkipped('Requires working frontend to test');

        // @phpstan-ignore-next-line Remove when test nolonger skipped
        User::create([
            'legal_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user = User::firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());

        // Manually generate the verification URL
        $verificationEmail = new VerifyEmail;
        $url = $verificationEmail->toMail($user)->actionUrl;
        $parts = parse_url($url);

        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';

        $relativeUrl = "$path?$query";

        // First request
        $this->actingAs($user)->get($relativeUrl);
        // Second request
        $response = $this->actingAs($user)->get($relativeUrl);

        $response->assertStatus(400);
    }

    #[Test]
    public function invalid_email_verification_link_fails(): void
    {
        $this->markTestSkipped('Requires working frontend to test');

        // @phpstan-ignore-next-line Remove when test nolonger skipped
        User::factory()->create([
            'legal_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user = User::firstOrFail();

        $params = ['id' => 1, 'hash' => 'abcdef'];
        $relativeUrl = route('verification.verify', $params, false);
        $signedUrl = URL::temporarySignedRoute('verification.verify', 10, $params);

        $response = $this->actingAs($user)->get($relativeUrl);

        // URL Signing fails
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get($signedUrl);

        // Invalid URL fails
        $response->assertStatus(403);
    }
}
