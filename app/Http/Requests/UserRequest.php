<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Orion\Http\Requests\Request;

class UserRequest extends Request
{
    /**
     * @return array<string, string[]>
     */
    public function commonRules(): array
    {
        return [
            'legal_name' => ['string'],
            'preferred_name' => ['string'],
            'birthday' => ['date', 'nullable'],
            'email' => ['email'],
            'password' => ['string'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function storeRules(): array
    {
        $request = new RegisterRequest;

        return $request->rules();
    }
}
