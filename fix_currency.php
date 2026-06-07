<?php
$files = [
    'Models/Booking.php', 
    'Models/BookingEventType.php', 
    'Models/BookingDailyMetric.php', 
    'app/Features/Analytics/Services/BookingAnalyticsService.php', 
    'app/Features/Analytics/Listeners/UpdateDailyMetricsListener.php', 
    'Http/Controllers/BookingController.php', 
    'Http/Controllers/BookingEventController.php', 
    'tests/Feature/BookingAnalyticsTest.php'
]; 
foreach ($files as $f) { 
    $path = 'Modules/Booking/' . $f; 
    if (file_exists($path)) { 
        $content = file_get_contents($path); 
        $content = preg_replace('/\'currency\'/', '\'currency_id\'', $content); 
        $content = preg_replace('/->currency\b/', '->currency_id', $content); 
        file_put_contents($path, $content); 
        echo "Fixed $f\n"; 
    } 
}
