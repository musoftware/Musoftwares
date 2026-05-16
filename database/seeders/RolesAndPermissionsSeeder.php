<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions (example)
        Permission::create(['name' => 'impersonate users']);
        Permission::create(['name' => 'manage currencies']);
        Permission::create(['name' => 'moderate marketplace']);
        Permission::create(['name' => 'manage subscriptions']);

        // Create Roles and assign created permissions
        $clientRole = Role::create(['name' => 'client']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
