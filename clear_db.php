<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (DB::select('SHOW PROCESSLIST') as $proc) {
    if ($proc->Id !== DB::connection()->getPdo()->query('SELECT CONNECTION_ID()')->fetchColumn()) {
        try {
            DB::statement('KILL ' . $proc->Id);
        } catch (\Exception $e) {
        }
    }
}
echo "Cleared DB locks.\n";
