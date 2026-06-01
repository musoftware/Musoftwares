<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "erp_support_tickets 52:\n";
print_r(\Illuminate\Support\Facades\DB::table('erp_support_tickets')->where('id', 52)->first() ?? 'Not found');
