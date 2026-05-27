<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \Illuminate\Support\Facades\DB::select('SELECT * FROM currencies LIMIT 1');
print_r($c);
