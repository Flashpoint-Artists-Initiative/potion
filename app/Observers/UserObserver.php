<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Auth\VerifyEmail;

class UserObserver
{
    public function updating(User $user): void
    {
        // If a user changes their already verified email, we need to reset the verification status
        if ($user->isDirty('email') && $user->hasVerifiedEmail()) {
            $user->email_verified_at = null;
        }
    }

    public function updated(User $user): void
    {
        // After a user updates their email, resend the verification email
        if ($user->wasChanged('email') && $user->wasChanged('email_verified_at') && ! $user->hasVerifiedEmail()) {
            $notification = app(VerifyEmail::class);
            $notification->url = Filament::getVerifyEmailUrl($user);

            $user->notify($notification);
        }
    }
}
