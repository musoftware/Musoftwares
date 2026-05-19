<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::all();
echo "USERS FOUND: " . $users->count() . "\n";
foreach ($users as $user) {
    echo "- ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Onboarding Completed: " . ($user->onboarding_completed ? 'YES' : 'NO') . "\n";
}
