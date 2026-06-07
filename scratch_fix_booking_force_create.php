<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/tests"));
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        $content = str_replace("Booking::forceCreate(['branch_id' => 1, 'status' => 'pending'])", "Booking::forceCreate(['branch_id' => 1, 'status' => 'pending', 'booking_event_type_id' => 1])", $content);
        $content = str_replace("Booking::forceCreate(['status' => 'pending'])", "Booking::forceCreate(['status' => 'pending', 'booking_event_type_id' => 1])", $content);
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
