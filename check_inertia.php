<?php
$baseDir = __DIR__;
$controllers = array_merge(
    glob($baseDir . '/app/Http/Controllers/*.php'),
    glob($baseDir . '/app/Http/Controllers/**/*.php'),
    glob($baseDir . '/Modules/*/Http/Controllers/*.php'),
    glob($baseDir . '/Modules/*/Http/Controllers/**/*.php')
);

$missing = [];
foreach ($controllers as $file) {
    if (!is_file($file)) continue;
    $content = file_get_contents($file);
    if (preg_match_all("/Inertia::render\(\s*['\"]([^'\"]+)['\"]\s*[,)]/", $content, $matches)) {
        foreach ($matches[1] as $path) {
            $f1 = $baseDir . "/resources/js/Pages/$path.tsx";
            $f2 = $baseDir . "/resources/js/Pages/$path.jsx";
            $f3 = $baseDir . "/resources/js/Pages/$path/Index.tsx";
            $f4 = $baseDir . "/resources/js/Pages/$path/Index.jsx";
            if (!file_exists($f1) && !file_exists($f2) && !file_exists($f3) && !file_exists($f4)) {
                $missing[] = "File: " . basename($file) . " -> Path: $path";
            }
        }
    }
}

if (empty($missing)) {
    echo "No missing pages found.\n";
} else {
    echo "Missing Pages:\n" . implode("\n", array_unique($missing)) . "\n";
}
