<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
echo "User: " . $user->email . "\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
$service = app(\App\Services\SubscriptionService::class);
echo "ERP Access: " . ($service->hasActiveSubscription($user, 'erp') ? 'Yes' : 'No') . "\n";
echo "Booking Access: " . ($service->hasActiveSubscription($user, 'booking') ? 'Yes' : 'No') . "\n";
