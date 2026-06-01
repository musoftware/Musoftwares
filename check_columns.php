<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$tables = ['invoice_cost_accruals', 'user_referral_commissions', 'gateway_payments', 'sms_payment_gateway_transactions', 'booking_daily_metrics', 'currencies'];
foreach($tables as $tableName) {
    if (\Schema::hasTable($tableName)) {
        $columns = \Schema::getColumnListing($tableName);
        $currCols = array_filter($columns, function($c) { return str_contains($c, 'currency'); });
        echo "$tableName: " . implode(', ', $currCols) . PHP_EOL;
    } else {
        echo "$tableName: TABLE NOT FOUND" . PHP_EOL;
    }
}
