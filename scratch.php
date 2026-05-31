<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$name = 'mahmoud sakr';
$user = \App\Models\User::where('name', 'like', "%{$name}%")->first();

if ($user) {
    // Adding 1000 to user_balance
    $amount = 1000;
    $user->user_balance += $amount;
    
    // Some systems also use points_balance
    $user->points_balance += $amount;
    
    $user->save();
    
    echo "Successfully added {$amount} to {$user->name}.\n";
    echo "New User Balance: " . $user->user_balance . "\n";
    echo "New Points Balance: " . $user->points_balance . "\n";
} else {
    echo "User not found in App\Models\User.\n";
}
