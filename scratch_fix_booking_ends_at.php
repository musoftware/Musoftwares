<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/tests"));
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // Also target forceCreate in booking to add booking_event_type_id, starts_at, ends_at
        $content = preg_replace_callback('/Booking::forceCreate\(\[(.*?)\]\)/s', function($m) {
            $inner = $m[1];
            if (strpos($inner, "'booking_event_type_id'") === false) {
                if ($inner !== '') $inner .= ", ";
                $inner .= "'booking_event_type_id' => 1";
            }
            if (strpos($inner, "'starts_at'") === false) {
                if ($inner !== '') $inner .= ", ";
                $inner .= "'starts_at' => now()";
            }
            if (strpos($inner, "'ends_at'") === false) {
                if ($inner !== '') $inner .= ", ";
                $inner .= "'ends_at' => now()->addHour()";
            }
            return "Booking::forceCreate([" . $inner . "])";
        }, $content);

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
