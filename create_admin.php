<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::firstOrCreate(
    ['email' => 'admin@musoftwares.com'],
    [
        'name' => 'Admin User',
        'password' => bcrypt('password')
    ]
);

$user->assignRole('admin');
echo "Admin user created successfully with email: admin@musoftwares.com and password: password\n";
