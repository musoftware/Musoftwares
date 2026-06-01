<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "message_messages schema:\n";
print_r(\Illuminate\Support\Facades\Schema::getColumnListing('message_messages'));

echo "\ncoworker_messages schema:\n";
print_r(\Illuminate\Support\Facades\Schema::getColumnListing('coworker_messages'));
