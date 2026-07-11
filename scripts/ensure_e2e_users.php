<?php

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Check DB connection
    \Illuminate\Support\Facades\DB::connection()->getPdo();
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Skipping E2E user creation.\n";
    exit(0);
}

use App\Models\User;
use Spatie\Permission\Models\Role;

$users = [
    [
        'email' => 'admin@musoftwares.com',
        'name' => 'E2E Admin User',
        'role' => 'admin',
    ],
    [
        'email' => 'tenant@musoftwares.com',
        'name' => 'E2E Tenant User',
        'role' => 'tenant_admin',
    ],
];

foreach ($users as $userData) {
    $user = User::where('email', $userData['email'])->first();
    if (!$user) {
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        echo "Created E2E user: {$userData['email']}\n";
    } else {
        echo "E2E user already exists: {$userData['email']}\n";
    }

    // Ensure they have the correct role assigned
    if (class_exists(Role::class)) {
        try {
            Role::findOrCreate($userData['role']);
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
                echo "Assigned role '{$userData['role']}' to {$userData['email']}\n";
            }
        } catch (\Exception $e) {
            echo "Warning: Could not assign role '{$userData['role']}' to {$userData['email']}: " . $e->getMessage() . "\n";
        }
    }
}
