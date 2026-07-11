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

        // Create Permissions
        $permissions = [
            'manage invoices',
            'manage withdrawals',
            'manage marketplace',
            'manage services',
            'manage users',
            'impersonate users',
            'manage settings',
            'manage referrals',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create Roles
        $roles = [
            'super_admin',
            'admin',
            'accountant',
            'moderator',
            'support_agent',
            'client',
            'seller',
            'employee',
            'tenant_admin'
        ];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName);

            // Assign permissions based on role
            if ($roleName === 'super_admin' || $roleName === 'admin') {
                $role->givePermissionTo(Permission::all());
            } elseif ($roleName === 'accountant') {
                $role->givePermissionTo(['manage invoices', 'manage withdrawals']);
            } elseif ($roleName === 'moderator') {
                $role->givePermissionTo(['manage marketplace', 'manage services', 'manage users']);
            } elseif ($roleName === 'support_agent') {
                $role->givePermissionTo(['impersonate users']);
            }
        }
    }
}
