<?php
use Illuminate\Contracts\Http\Kernel;
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'preferred_currency')) {
    \Illuminate\Support\Facades\Schema::table('users', function($table) {
        $table->dropColumn(['preferred_currency', 'preferred_currency_locked_at']);
    });
    echo "Columns dropped.\n";
} else {
    echo "No columns to drop.\n";
}
