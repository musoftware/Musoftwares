<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sub = \Modules\Tools\Models\ToolSubscription::first();
echo json_encode($sub);
echo "\n\n";

$db = \Illuminate\Support\Facades\DB::select('DESCRIBE tool_subscriptions');
echo json_encode($db);
