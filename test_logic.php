<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = App\Models\User::first();
    echo "User ID: {$user->id}\n";
    $tenant = Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
    if (!$tenant) {
        $tenant = Modules\ERP\Models\Tenant::create([
            'user_id' => $user->id,
            'name' => 'Test Tenant',
            'status' => 'active',
            'base_currency_id' => 1,
        ]);
        echo "Created Tenant ID: {$tenant->id}\n";
    } else {
        echo "Found Tenant ID: {$tenant->id}\n";
    }

    $userTenant = Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
    if ($userTenant) {
        echo "User Tenant lookup success: {$userTenant->id}\n";
    }
    
    // Simulate SubscriptionController Fix
    echo "Simulating Feature assignment logic...\n";
    $feature = App\Models\TenantFeature::create([
        'tenant_id' => $userTenant->id,
        'feature_key' => 'erp',
        'module' => 'erp',
        'expires_at' => \Carbon\Carbon::now()->addDays(14)
    ]);
    echo "Feature assigned successfully: {$feature->id}\n";
    
    // Cleanup
    $feature->delete();
    
    echo "SUCCESS: Logic works without crashing!\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
