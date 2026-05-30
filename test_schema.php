<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
use Illuminate\Support\Facades\Schema;
echo Schema::hasTable('gold_world_prices') ? 'old_world_prices_exists' : 'missing';
echo "\n";
echo Schema::hasTable('gold_prices') ? 'old_gold_prices_exists' : 'missing';
