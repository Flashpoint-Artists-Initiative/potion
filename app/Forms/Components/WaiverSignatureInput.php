<?php

declare(strict_types=1);

namespace App\Forms\Components;

use App\Models\User;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;

/**
 * TODO: Setting $user as static sucks, fix later.
 */
class WaiverSignatureInput extends TextInput
{
    protected static int|User|Closure|null $user = null;

    public function getUser(): ?User
    {
        $user = static::$user;

        if (is_callable($user)) {
            $user = $this->evaluate($user);
        }

        if (is_int($user)) {
            $user = User::find($user);
        }

        return $user instanceof User ? $user : Auth::user();
    }

    public static function makeWithUser(string $name, int|User|Closure|null $user = null): static
    {
        static::$user = $user;
        $self = static::make($name);

        return $self;
    }

    protected function setUp(): void
    {
        /** @var User */
        $user = $this->getUser();
        $username = $user->legal_name;

        /** @var string $spacedUsername */
        $spacedUsername = preg_replace('/\s+/', ' ', trim($username));
        $options = [$username, $spacedUsername, strtolower($username), strtolower($spacedUsername)];

        $this->label('I agree to the terms of the waiver and understand that I am signing this waiver electronically.')
            ->helperText('You must enter your full legal name as it is shown on your ID and listed in your profile.')
            ->required()
            ->placeholder($spacedUsername)
            ->in($options)
            ->mutateStateForValidationUsing(fn (?string $state) => $state ? preg_replace('/\s+/', ' ', trim($state)) : null)
            ->validationMessages([
                'required' => 'You must agree to the terms of the waiver and sign it.',
                'in' => 'The entered value must match your legal name, as listed in your profile: ' . $spacedUsername . '.',
            ]);
    }
}
