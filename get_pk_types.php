<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$curr = \Illuminate\Support\Facades\Schema::getColumnType('currencies', 'id');
$count = \Illuminate\Support\Facades\Schema::getColumnType('countries', 'id');
echo "Currencies ID: $curr\nCountries ID: $count\n";
