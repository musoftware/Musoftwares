<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_required_roles()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $expectedRoles = [
            'super_admin',
            'admin',
            'accountant',
            'moderator',
            'support_agent',
            'client',
            'freelancer',
            'seller',
            'employee',
            'tenant_admin',
        ];

        foreach ($expectedRoles as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role,
            ]);
        }
    }
}
