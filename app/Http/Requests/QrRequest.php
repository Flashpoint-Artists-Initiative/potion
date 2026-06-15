<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QrRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['event_id' => $this->route('event_id')]);
    }
}
