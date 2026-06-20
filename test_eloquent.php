<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tx = new \Modules\GoldSavers\Models\GoldTransaction();
$tx->type = 'buy';
$tx->grams = 10;
$tx->current_value = 1500;

echo json_encode($tx->toArray());
