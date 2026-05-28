<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::statement('SET SESSION FOREIGN_KEY_CHECKS=0;');

// Some databases require this config to persist across queries in the same PDO connection
\Illuminate\Support\Facades\DB::connection()->getPdo()->exec('SET SESSION FOREIGN_KEY_CHECKS=0;');

\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo trim(\Illuminate\Support\Facades\Artisan::output()) . "\n";

\Illuminate\Support\Facades\DB::statement('SET SESSION FOREIGN_KEY_CHECKS=1;');
echo "Done!\n";
