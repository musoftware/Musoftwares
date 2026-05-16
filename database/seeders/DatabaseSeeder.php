<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CurrenciesSeeder::class,
            SiteSettingsSeeder::class,
            AdminUserSeeder::class,
        ]);

        $client = \App\Models\User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Client User',
                'password' => bcrypt('password'),
                'role' => 'client',
                'email_verified_at' => now(),
            ]
        );
        $client->assignRole('client');
    }
}
