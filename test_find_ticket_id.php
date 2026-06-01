<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
$res = [];
foreach($tables as $t) {
    $tableName = array_values((array)$t)[0];
    if (\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'ticket_id')) {
        $res[] = $tableName;
    }
}
echo "Tables with ticket_id:\n";
print_r($res);
