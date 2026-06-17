<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$inv = \App\Models\Invoice::latest()->first();
echo "Invoice ID: " . $inv->id . " Currency ID: " . $inv->currency_id . "\n";
echo "Currencies: " . json_encode(\App\Models\Currency::pluck('currency', 'id')->toArray()) . "\n";

$ex = \App\Models\CurrenciesExchange::where('currency1', 1)->where('currency2', 2)->orderBy('id', 'desc')->first();
if ($ex) {
    echo "1 to 2 rate: " . $ex->rate . "\n";
}

$ex2 = \App\Models\CurrenciesExchange::where('currency1', 2)->where('currency2', 1)->orderBy('id', 'desc')->first();
if ($ex2) {
    echo "2 to 1 rate: " . $ex2->rate . "\n";
}
