<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
foreach(\DB::select('SHOW TABLES') as $table) {
    $tableName = reset($table);
    $columns = \Schema::getColumnListing($tableName);
    if(in_array('currency', $columns) || in_array('fee_currency', $columns) || in_array('converted_currency', $columns) || in_array('reward_currency', $columns) || in_array('spend_currency', $columns) || in_array('spent_currency', $columns) || in_array('hour_rate_currency', $columns) || in_array('booking_rate_currency', $columns)) {
        echo $tableName . PHP_EOL;
    }
}
