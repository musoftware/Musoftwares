<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/tests"));
$count = 0;
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // Remove tenant_id from BookingEventType::forceCreate, GroupSession::forceCreate, etc.
        $content = preg_replace_callback("/(BookingEventType|GroupSession|BookingGroupSession)::(forceCreate|create|factory\(\)->create)\(\[(.*?)\]\)/s", function($m) {
            $inner = $m[3];
            $inner = preg_replace("/'tenant_id'\s*=>\s*[^,\]]+,?\s*/", "", $inner);
            return $m[1] . "::" . $m[2] . "([" . $inner . "])";
        }, $content);

        // Remove tenant_id from GroupCapacityManagerTest manually
        if (strpos($file->getPathname(), "GroupCapacityManagerTest.php") !== false) {
             $content = preg_replace("/'tenant_id'\s*=>\s*[^,\]]+,?\s*/", "", $content);
        }

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}
echo "Total updated: $count\n";
