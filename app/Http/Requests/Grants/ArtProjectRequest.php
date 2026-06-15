<?php

declare(strict_types=1);

namespace App\Http\Requests\Grants;

use App\Enums\ArtProjectStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Orion\Http\Requests\Request;

class ArtProjectRequest extends Request
{
    /**
     * @return array<string, list<Enum|string>>
     */
    public function storeRules(): array
    {
        return [
            'name' => ['string', 'required'],
            'user_id' => ['integer', 'required', 'exists:users,id'],
            'event_id' => ['integer', 'required', 'exists:events,id'],
            'artist_name' => ['string'],
            'description' => ['string'],
            'budget_link' => ['nullable', 'string', 'url'],
            'min_funding' => ['integer', 'required'],
            'max_funding' => ['integer', 'required'],
            'project_status' => ['string', 'required', Rule::enum(ArtProjectStatusEnum::class)],
        ];
    }

    /**
     * @return array<string, list<Enum|string>>
     */
    public function updateRules(): array
    {
        return [
            'name' => ['string'],
            'user_id' => ['integer', 'exists:users,id'],
            'event_id' => ['integer', 'exists:events,id'],
            'artist_name' => ['string'],
            'description' => ['string'],
            'budget_link' => ['nullable', 'string', 'url'],
            'min_funding' => ['integer'],
            'max_funding' => ['integer'],
            'project_status' => ['string', Rule::enum(ArtProjectStatusEnum::class)],
        ];
    }
}
