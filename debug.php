<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $client = App\Models\User::find(82);
    if (!$client) { echo "Client not found"; exit; }
    $invoice = App\Models\Invoice::createInvoice($client, null, null);
    echo "Invoice created: " . $invoice->id;
} catch(\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
