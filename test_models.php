<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = glob(__DIR__ . '/app/Models/*.php');
foreach ($files as $file) {
    try {
        require_once $file;
        echo basename($file) . " OK\n";
    } catch (\Throwable $e) {
        echo basename($file) . " ERROR: " . $e->getMessage() . "\n";
    }
}
