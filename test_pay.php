<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->loadEnvironmentFrom('.env.local');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$invoice = App\Models\Invoice::latest()->first();
$request = new Illuminate\Http\Request([
    'service_amount' => '100',
    'currency' => 2,
    'service_pay_source' => 'wallet',
    'service_pay_dest' => 'cib_swype',
    'service_revenue' => 0
]);

$controller = app(App\Http\Controllers\Admin\InvoiceController::class);
try {
    $res = $controller->calculatePayService($request, $invoice);
    echo $res->getContent();
} catch (\Throwable $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
