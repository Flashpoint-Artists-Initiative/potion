<?php

declare(strict_types=1);

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class WaiverSignatureInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var User */
        $user = Auth::user();
        $username = $user->legal_name;

        /** @var string $spacedUsername */
        $spacedUsername = preg_replace('/\s+/', ' ', trim($username));
        $options = [$username, $spacedUsername];

        $this->label('I agree to the terms of the waiver and understand that I am signing this waiver electronically.')
            ->helperText('You must enter your full legal name as it is shown on your ID and listed in your profile.')
            ->required()
            ->placeholder($spacedUsername)
            ->in($options)
            ->mutateStateForValidationUsing(fn (?string $state) => $state ? preg_replace('/\s+/', ' ', trim($state)) : null)
            ->validationMessages([
                'required' => 'You must agree to the terms of the waiver and sign it.',
                'in' => 'The entered value must match your legal name, as listed in your profile.',
            ]);
    }
}
