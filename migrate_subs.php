<?php

use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;

$users = User::whereNotNull('plan_id')->get();

$migratedCount = 0;

foreach ($users as $user) {
    $planName = strtolower($user->subscription_plan);
    $legacyFeatures = [];
    
    $isGeneric = in_array(trim($planName), ['yearly plan', 'monthly plan', 'trial', '3 years plan', 'starter', 'pro', 'enterprise', 'free']);
    
    if (str_contains($planName, 'erp') || $isGeneric) {
        $legacyFeatures[] = 'erp';
    }
    if (str_contains($planName, 'crm') || $isGeneric) {
        $legacyFeatures[] = 'crm';
    }
    if (str_contains($planName, 'gold')) {
        $legacyFeatures[] = 'gold-saver';
    }
    if (str_contains($planName, 'booking')) {
        $legacyFeatures[] = 'booking';
    }
    if (str_contains($planName, 'affiliate') || str_contains($planName, 'pos')) {
        $legacyFeatures[] = 'affiliate-pos';
    }

    // For Custom Plans, maybe we can assume erp and crm for now if we can't parse it?
    if (str_contains($planName, 'custom plan')) {
        $legacyFeatures[] = 'erp';
        $legacyFeatures[] = 'crm';
    }

    foreach ($legacyFeatures as $lf) {
        UserSubscription::updateOrCreate(
            ['client_id' => $user->id, 'object' => $lf],
            [
                'status' => 'active',
                'started_at' => now(), // Can't know exact start, default to now
                'expires_at' => $user->subscription_date ?? Carbon::now()->addYear(),
                'auto_renew' => $user->subscription_force ?? false,
            ]
        );
        $migratedCount++;
    }
}

echo "Migrated $migratedCount features for " . $users->count() . " users.\n";
