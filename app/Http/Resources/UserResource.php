<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'legal_name' => $this->legal_name,
            'preferred_name' => $this->preferred_name,
            'display_name' => $this->display_name, // Virtual attribute
            'birthday' => $this->birthday,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at != null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at ?? new MissingValue,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => $this->formatPermissions(),
            'purchased_tickets' => PurchasedTicketResource::collection($this->whenLoaded('purchasedTickets')),
            'reserved_tickets' => ReservedTicketResource::collection($this->whenLoaded('reservedTickets')),
            'ticketTransfers' => TicketTransferResource::collection($this->whenLoaded('ticketTransfers')),
            'waivers' => WaiverResource::collection($this->whenLoaded('waivers')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
        ];
    }

    /**
     * @return MissingValue|Collection<int, Permission>
     */
    protected function formatPermissions(): Collection|MissingValue
    {
        if (! $this->relationLoaded('permissions')) {
            return new MissingValue;
        }

        $collection = $this->getAllPermissions();

        $permissions = $collection->map(fn ($p) => $p->name);
        foreach (config('permission.implement.defaultPermissions') as $value) {
            $permissions->push($value);
        }

        return $permissions;
    }
}
