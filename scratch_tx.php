<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'ahmedmaher.paypal@gmail.com')->first();
if ($user) {
    echo "User ID: " . $user->id . "\n";
    $txs = \App\Models\Transaction::where('user_id', $user->id)->orderBy('created_at', 'asc')->take(5)->get(['id', 'amount', 'created_at']);
    foreach ($txs as $tx) {
        echo "{$tx->id} | {$tx->amount} | {$tx->created_at}\n";
    }

    echo "Lowest ID transactions:\n";
    $txs2 = \App\Models\Transaction::where('user_id', $user->id)->orderBy('id', 'asc')->take(5)->get(['id', 'amount', 'created_at']);
    foreach ($txs2 as $tx) {
        echo "{$tx->id} | {$tx->amount} | {$tx->created_at}\n";
    }
} else {
    echo "User not found\n";
}
