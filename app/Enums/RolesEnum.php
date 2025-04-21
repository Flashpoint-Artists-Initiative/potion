<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Spatie\Permission\Models\Role;

enum RolesEnum: string implements HasLabel
{
    case Admin = 'admin';
    case EventManager = 'event-manager';
    case BoxOffice = 'box-office';
    case ArtGrantReviewer = 'art-grant-reviewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::EventManager => 'Event Manager',
            self::BoxOffice => 'Box Office',
            self::ArtGrantReviewer => 'Art Grant Reviewer',
        };
    }

    public function model(): Role
    {
        return Role::where('name', $this->value)->firstOrFail();
    }

    public static function fromId(int $id): static
    {
        $role = Role::findOrFail($id);

        return self::from($role->name);
    }

    public static function fromModel(Role $role): static
    {
        return self::from($role->name);
    }
}
