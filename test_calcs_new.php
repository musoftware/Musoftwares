<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

config(['database.connections.mysql.database' => 'u962989541_db']);
config(['database.connections.mysql.username' => 'root']);
config(['database.connections.mysql.password' => '']);
DB::purge('mysql');
DB::reconnect('mysql');

$user = App\Models\User::find(1);
if(!$user) {
    echo "User 1 not found.\n";
    exit;
}
echo "--- New Project ---\n";
echo "User 1 Balance: " . $user->balance() . "\n";
echo "User 1 Available Balance: " . $user->available_balance() . "\n";

$income = (float) \App\Models\Transaction::where('type', 'received')->sum('business_amount');
$expenses = (float) \App\Models\CostTransaction::sum('business_amount');

echo "Dashboard Income (All Time Received Transactions): " . $income . "\n";
echo "Dashboard Expenses (All Time Cost Transactions): " . $expenses . "\n";
