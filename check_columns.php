<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
foreach($tables as $t) {
    $tableName = array_values((array)$t)[0];
    if (str_starts_with($tableName, 'erp_')) {
        $columns = Schema::getColumnListing($tableName);
        if (!in_array('deleted_at', $columns)) {
            echo $tableName . PHP_EOL;
        }
    }
}
