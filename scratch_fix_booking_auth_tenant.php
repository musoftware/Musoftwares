<?php

$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('Modules/Booking'));
$count = 0;

foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        $original = $content;
        
        // 1. auth()->user()->tenant_id
        $content = str_replace(
            'auth()->user()->tenant_id',
            "(app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id())",
            $content
        );

        // 2. Also remove `tenant_id` from User::forceCreate/create in tests
        if (strpos($path, 'tests') !== false) {
            $content = preg_replace("/'tenant_id'\s*=>\s*[^,\]]+,?\s*/", "", $content);
        }
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
            $count++;
        }
    }
}

echo "Total updated: $count\n";
