<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$wh = \App\Models\IncomingWebhook::orderBy("id", "desc")->first();
echo json_encode($wh ? $wh->toArray() : null, JSON_PRETTY_PRINT);

