<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = new App\Models\User();
$u->name = 'Test User';
$u->email = 'test@example.com';
$u->password = bcrypt('password');
$u->save();
echo "User created with ID: " . $u->id;
