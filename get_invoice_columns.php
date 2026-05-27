<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$i = \Illuminate\Support\Facades\Schema::getColumnListing('erp_invoices');
print_r($i);
