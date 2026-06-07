<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Booking\app\Features\Widget\Models\BookingWidget;

$widget = new BookingWidget(['tenant_id' => 1, 'name' => 'Test Widget']);
var_dump($widget->getAttributes());
