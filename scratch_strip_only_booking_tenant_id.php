<?php

$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/tests"));
$count = 0;
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // ONLY strip tenant_id from Booking creations (Booking::forceCreate, Booking::create, Booking::factory)
        $content = preg_replace_callback("/Booking::(forceCreate|create|factory\(\)->create)\(\[(.*?)\]\)/s", function($m) {
            $inner = $m[2];
            $inner = preg_replace("/'tenant_id'\s*=>\s*[^,\]]+,?\s*/", "", $inner);
            return "Booking::" . $m[1] . "([" . $inner . "])";
        }, $content);

        // Strip from SmsTemplateRendererTest.php the bad Booking initialization if any
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated Booking tenant_id: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}
echo "Total updated: $count\n";
