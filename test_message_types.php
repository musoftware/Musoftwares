<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Distinct thread_type in message_messages:\n";
print_r(\Illuminate\Support\Facades\DB::table('message_messages')->select('thread_type')->distinct()->pluck('thread_type')->toArray());

echo "\nRows where thread_id = 52 in message_messages:\n";
print_r(\Illuminate\Support\Facades\DB::table('message_messages')->where('thread_id', 52)->get()->toArray());
