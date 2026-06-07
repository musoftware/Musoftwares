<?php

$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("Modules/Booking/app/Features"));

foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        if (strpos($content, '$this->middleware') !== false) {
            
            // Add implements HasMiddleware
            if (strpos($content, 'implements \Illuminate\Routing\Controllers\HasMiddleware') === false) {
                $content = preg_replace("/class ([a-zA-Z0-9_]+) extends Controller\s*\{/s", "class $1 extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware\n{", $content);
            }

            // Convert string middleware: $this->middleware('feature:booking-analytics');
            if (preg_match("/\\\$this->middleware\('([^']+)'\);/", $content)) {
                $content = preg_replace("/public function __construct.*\\\$this->middleware\('([^']+)'\);\s*}/s", 
                "public static function middleware(): array\n    {\n        return [\n            new \Illuminate\Routing\Controllers\Middleware('$1')\n        ];\n    }", $content);
            }
            
            // Convert closure middleware
            if (preg_match("/\\\$this->middleware\(function \(\\\$request, \\\$next\) \{.*?\n\s*\}\);/s", $content)) {
                $content = preg_replace("/public function __construct.*\\\$this->middleware\((function \(\\\$request, \\\$next\) \{.*?\n\s*\})\);\s*}/s", 
                "public static function middleware(): array\n    {\n        return [\n            new \Illuminate\Routing\Controllers\Middleware($1)\n        ];\n    }", $content);
            }

            file_put_contents($file->getPathname(), $content);
            echo "Migrated to HasMiddleware: " . $file->getPathname() . "\n";
        }
    }
}
