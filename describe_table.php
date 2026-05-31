<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Schema::getColumns('sms_payment_gateway_transactions');
foreach ($columns as $col) {
    echo str_pad($col['name'], 20) . " | " . ($col['nullable'] ? "NULL" : "NOT NULL") . "\n";
}
