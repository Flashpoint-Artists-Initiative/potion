<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Volunteering\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shift
 */
class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team' => $this->team->name,
            'title' => $this->title,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'shift_type_id' => $this->shift_type_id,
            'length' => $this->length,
            'num_spots' => $this->num_spots,
            'volunteers_count' => $this->volunteers_count,
            'percent_filled' => $this->percentFilled,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'shift_type' => $this->whenLoaded('team'),
        ];
    }
}
