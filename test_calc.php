<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$inv = \App\Models\Invoice::first();
echo "Invoice ID: " . $inv->id . "\n";
echo "Invoice Currency: " . $inv->currency_id . "\n";

// Emulate request
$req = new \Illuminate\Http\Request([
    'service_amount' => 1,
    'currency' => 2, // EGP
    'service_pay_source' => 'wallet',
    'service_pay_dest' => 'cib_swype',
    'service_revenue' => 0
]);

$controller = new \App\Http\Controllers\Admin\InvoiceController();
$res = $controller->calculatePayService($req, $inv);
echo $res->getContent() . "\n";
