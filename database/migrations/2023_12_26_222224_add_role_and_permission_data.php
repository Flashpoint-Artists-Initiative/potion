<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $data = config('permission.implement');
        foreach ($data['permissions'] as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach ($data['roles'] as $role => $permissions) {
            Role::findOrCreate($role)->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::query()->delete();
        Role::query()->delete();
    }
};
