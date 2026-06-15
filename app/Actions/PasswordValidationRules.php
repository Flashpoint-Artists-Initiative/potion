<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            new Password(config('auth.password_min_length')),
        ];
    }
}
