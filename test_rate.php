<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$currencyId = 2; // EGP
$baseRate = \App\Helpers\FinanceHelper::calculateOverheadHourlyRate();
$businessCurrency = \App\Models\AdminSettings::GetValue('business_currency', 2);

echo "Base Rate: " . $baseRate . "\n";
echo "Business Currency: " . $businessCurrency . "\n";

$rate = \App\Models\CurrenciesExchange::RateToday(
    $baseRate,
    $businessCurrency,
    $currencyId
);

echo "Rate Today: " . $rate . "\n";

$currencyModel = \App\Models\Currency::find($currencyId);
if ($currencyModel) {
    try {
        $fixed_rate = \App\Helpers\FinanceHelper::instance()->price_fixer($rate, $currencyModel->currency);
        echo "Fixed Rate: " . $fixed_rate . "\n";
    } catch (\Exception $e) {
        echo "Error in price_fixer: " . $e->getMessage() . "\n";
    }
}
