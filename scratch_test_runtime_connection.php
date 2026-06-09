<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

try {
    $r = \Illuminate\Support\Facades\Http::timeout(5)->post('http://127.0.0.1:18400/auth/callback', ['device_code' => 'test']);
    dump($r->status());
    dump($r->body());
} catch (\Exception $e) {
    dump($e->getMessage());
}
