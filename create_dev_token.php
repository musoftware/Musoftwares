<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Check if a user exists, or create a default one
$user = User::first();
if (!$user) {
    $user = User::create([
        'id' => 1,
        'name' => 'Developer',
        'email' => 'dev@musoftwares.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "Default user created with ID 1.\n";
} else {
    echo "Found existing user with ID: " . $user->id . " and Email: " . $user->email . "\n";
}

// Generate personal access token
$tokenResult = $user->createToken('runtime-token');
$token = $tokenResult->plainTextToken;

echo "\n=============================================\n";
echo "YOUR NEW DEVELOPER TOKEN:\n";
echo $token . "\n";
echo "=============================================\n";
