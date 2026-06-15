<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  JWTGuard  $guard
     * @return void
     */
    public function __construct(protected Guard $guard) {}

    /**
     * Login
     *
     * Log a user in, returning a new JWT
     *
     * @unauthenticated
     */
    public function loginAction(LoginRequest $request): JsonResponse
    {
        if (config('auth.lowercase_usernames')) {
            $request->merge(['email' => Str::lower($request->email)]);
        }

        if (! $token = $this->guard->attempt($request->only(['email', 'password']))) {
            $this->throwFailedAuthenticationException($request);
        }

        /** @var User $user */
        $user = Auth::user();

        $collection = $user->getAllPermissions();

        $permissions = $collection->map(fn ($p) => $p->name);

        // Returns a new JWT
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard->factory()->getTTL() * 60,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Logout
     *
     * Logout the authenticated token
     */
    public function logoutAction(): JsonResponse
    {
        $this->guard->logout();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * User Registration
     *
     * Register a new User, returning a new JWT on success
     *
     * @unauthenticated
     */
    public function registerAction(RegisterRequest $request): JsonResponse
    {
        if (config('auth.lowercase_usernames')) {
            $request->merge([
                'email' => Str::lower($request->email),
            ]);
        }

        event(new Registered($user = User::create($request->validated())));

        $token = $this->guard->login($user);

        // Returns a new JWT
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard->factory()->getTTL() * 60,
        ], 201);
    }

    /**
     * Forgot password
     *
     * Send a password reset link to the submitted email
     *
     * @unauthenticated
     */
    public function forgotPasswordAction(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 200);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * Reset password
     *
     * Reset the user's password using the token sent to their email
     *
     * @unauthenticated
     */
    public function resetPasswordAction(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        $errorField = 'password';

        if ($status == Password::INVALID_USER) {
            $errorField = 'email';
        }

        if ($status == Password::INVALID_TOKEN) {
            $errorField = 'token';
        }

        throw ValidationException::withMessages([
            $errorField => [trans($status)],
        ]);
    }

    /**
     * Verify a user's email address
     *
     * Mark the authenticated user's email address as verified.
     */
    public function verifyEmailAction(EmailVerificationRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $request->fulfill();

        return response()->json(['message' => 'Your email has been verified'], 202);
    }

    /**
     * Resend the email verification link
     *
     * Resend the email verification link to the user
     */
    public function resendVerificationEmailAction(Request $request): JsonResponse
    {
        /** @var User */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email verification link sent.'], 202);
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @param  Request  $request
     * @return void
     *
     * @throws ValidationException
     */
    protected function throwFailedAuthenticationException($request)
    {
        // $this->limiter->increment($request);

        throw ValidationException::withMessages([
            'password' => [trans('auth.failed')],
        ]);
    }
}
