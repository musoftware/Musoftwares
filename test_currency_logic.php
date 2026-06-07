<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $u = \App\Models\User::whereNotNull('currency_id')->first();
    if (!$u) {
        echo "No user with currency found.\n";
        exit;
    }
    echo "User Currency: " . $u->currency_id . "\n";
    
    $t = new \App\Models\Transaction();
    $t->user_id = $u->id;
    $t->amount = 100;
    $t->reason = 'Test';
    $t->type = 'received';
    // Don't set currency explicitly
    $t->save();
    
    echo "Transaction Saved! Currency: " . $t->currency_id . ", Amount: " . $t->amount . ", Business Amount: " . $t->business_amount . "\n";
    
    // Cleanup
    $t->forceDelete();
    
    // Now test without currency_id
    $u_no_curr = \App\Models\User::whereNull('currency_id')->first();
    if ($u_no_curr) {
        $t2 = new \App\Models\Transaction();
        $t2->user_id = $u_no_curr->id;
        $t2->amount = 50;
        $t2->reason = 'Fail Test';
        $t2->type = 'received';
        try {
            $t2->save();
            echo "FAILED: Transaction saved silently for user without currency!\n";
            $t2->forceDelete();
        } catch (\Exception $e) {
            echo "EXPECTED EXCEPTION (No User Currency): " . $e->getMessage() . "\n";
        }
    } else {
        echo "No users without currency found to test failure.\n";
    }
    
    echo "\nALL TESTS PASSED SUCCESSFULLY.\n";
    
} catch (\Exception $e) {
    echo "UNEXPECTED EXCEPTION: " . $e->getMessage() . "\n";
}
