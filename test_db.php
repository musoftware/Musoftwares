<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$c = DB::table('countries')->where('name', 'Egypt')->first();
$cities = DB::table('cities')->where('country_id', $c->id)->pluck('name')->take(10)->toArray();
print_r($cities);
