<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

var_dump(config('sms-payment-gateway.allowed_senders'));
var_dump(config('sms-payment-gateway'));
