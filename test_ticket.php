<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    echo json_encode(\App\Models\Ticket::first()->toArray());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
