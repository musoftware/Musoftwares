<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invoice = \App\Models\Invoice::with('items')->find(4183);
if($invoice) {
    $res = new \App\Http\Resources\InvoiceResource($invoice);
    echo json_encode($res->resolve());
} else {
    echo "Not found";
}
