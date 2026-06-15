<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PopulatePermissionsAndRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the database with the roles and permissions defined in config/permission.php';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $data = config('permission.implement');
        foreach ($data['permissions'] as $permission) {
            /** @var Permission $model */
            $model = Permission::findOrCreate($permission);

            if ($model->wasRecentlyCreated) {
                $this->info("Permission {$model->name} created");
            }
        }

        foreach ($data['roles'] as $role => $permissions) {
            /** @var Role $model */
            $model = Role::findOrCreate($role)->syncPermissions($permissions);
            $this->info("Role {$model->name} permissions synced");
        }
    }
}
