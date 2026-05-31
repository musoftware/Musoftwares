<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$year = 2026;
$month = 5;
$startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
$endOfMonth = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

$startDate = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
$endDate = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

$busyTimes = \App\Models\RecurringBusyTime::where('is_active', true)->get();
echo "Busy times count: " . $busyTimes->count() . "\n";

$period = new \DatePeriod(
    $startDate,
    new \DateInterval('P1D'),
    $endDate->copy()->addDay()
);

foreach ($period as $date) {
    $dateStr = $date->format('Y-m-d');
    $dayOfWeek = $date->format('l');

    foreach ($busyTimes as $bt) {
        if ($bt->is_recurring) {
            echo "Checking $dateStr ($dayOfWeek) vs {$bt->day_of_week}\n";
            if (strcasecmp($bt->day_of_week, $dayOfWeek) === 0) {
                echo "MATCHED!\n";
            }
        }
    }
}
