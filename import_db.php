<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

Illuminate\Support\Facades\DB::unprepared(file_get_contents('d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\u962989541_db (2).sql'));
echo 'Database imported successfully.';

