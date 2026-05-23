<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    $users = User::all();
    echo "Processing " . $users->count() . " users...\n";

    // 1. Ensure Roles exist
    $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $clientRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
    $freelancerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'freelancer', 'guard_name' => 'web']);

    foreach ($users as $user) {
        echo "User: {$user->email} ({$user->name})\n";

        // Complete onboarding
        $user->onboarding_completed = true;
        $user->country = $user->country ?: 'Egypt';
        $user->city = $user->city ?: 'Cairo';
        $user->mobile_1 = $user->mobile_1 ?: '+201012345678';
        $user->preferred_currency = $user->preferred_currency ?: 'USD';
        $user->preferred_currency_locked_at = $user->preferred_currency_locked_at ?: now();
        $user->tour_completed = true;
        $user->kyc_verified = true;
        $user->kyc_verified_at = now();
        $user->kyc_provider = 'Manual Seeder';
        $user->save();

        // Assign Roles based on email
        if (str_contains($user->email, 'admin')) {
            $user->assignRole('admin');
            echo " - Assigned role: admin\n";
        } elseif (str_contains($user->email, 'client')) {
            $user->assignRole('client');
            echo " - Assigned role: client\n";
        } else {
            $user->assignRole('freelancer');
            echo " - Assigned role: freelancer\n";
        }

        // Initialize / Seed Balance
        $user->user_balance = 50000.00;
        $user->pending_commission = 15000.00;
        $user->save();
        echo " - Balance seeded. Balance: {$user->user_balance}\n";

        // Seed some points/connects for bidding (e.g. 200 points)
        $currentPoints = $user->points_balance;
        if ($currentPoints < 200) {
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'points' => 200 - $currentPoints,
                'description' => 'Onboarding points bonus (Seeder)',
            ]);
            echo " - Bidding Connects seeded. Current Balance: 200 points\n";
        }
    }

    DB::commit();
    echo "\nONBOARDING AND BALANCES SUCCESSFULLY SEEDED!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR SEEDING: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
