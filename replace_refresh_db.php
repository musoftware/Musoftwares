<?php

$dirs = ['tests', 'Modules'];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            // Only process tests
            if (strpos($path, 'Tests') === false && strpos($path, 'tests') === false) {
                continue;
            }

            $content = file_get_contents($path);
            if (strpos($content, 'RefreshDatabase') !== false) {
                $content = str_replace([
                    'Illuminate\Foundation\Testing\RefreshDatabase',
                    'use RefreshDatabase;',
                    'use RefreshDatabase, WithFaker;',
                    'RefreshDatabase::class'
                ], [
                    'Illuminate\Foundation\Testing\DatabaseTransactions',
                    'use DatabaseTransactions;',
                    'use DatabaseTransactions, WithFaker;',
                    'DatabaseTransactions::class'
                ], $content);
                file_put_contents($path, $content);
                echo "Updated: $path\n";
            }
        }
    }
}

echo "Done.\n";
