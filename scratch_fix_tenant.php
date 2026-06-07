<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/tests"));
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        $content = preg_replace("/\['tenant_id'\s*=>\s*[^,\]]+,\s*/", "[", $content);
        $content = preg_replace("/\['tenant_id'\s*=>\s*[^,\]]+\]/", "[]", $content);
        $content = preg_replace("/,\s*'tenant_id'\s*=>\s*[^,\]]+/", "", $content);
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
