<?php

$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/app/Features"));

foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // If it contains $this->middleware
        if (strpos($content, '$this->middleware') !== false) {
            
            // Remove $this->middleware('auth:sanctum'); entirely
            $content = preg_replace("/\\\$this->middleware\('auth:sanctum'\);\s*/", "", $content);

            // Check if there are still middleware calls
            if (strpos($content, '$this->middleware') !== false) {
                // It has closure or feature middleware!
                // Add implements HasMiddleware
                if (strpos($content, 'implements \Illuminate\Routing\Controllers\HasMiddleware') === false) {
                    $content = preg_replace("/class ([a-zA-Z0-9_]+) extends Controller\s*\{/s", "class $1 extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware\n{", $content);
                }

                // Convert __construct to middleware() method
                // Note: this regex assumes __construct ONLY contains $this->middleware
                // Let's manually replace the ones that have closures since there are only a few.
            }

            file_put_contents($file->getPathname(), $content);
            echo "Updated middleware in: " . $file->getPathname() . "\n";
        }
    }
}
