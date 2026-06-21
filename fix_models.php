<?php
$directories = [
    __DIR__ . '/app/Models',
    __DIR__ . '/Modules/*/Models',
];

$files = [];
foreach ($directories as $dirPattern) {
    $globDirs = glob($dirPattern, GLOB_ONLYDIR) ?: [];
    if (strpos($dirPattern, '*') === false) {
        $globDirs[] = $dirPattern;
    }
    
    foreach ($globDirs as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
}

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;

    // Check if class extends Model but doesn't use SoftDeletes
    if (preg_match('/class\s+\w+\s+extends\s+(Model|Authenticatable|BaseModel)/', $content)) {
        if (strpos($content, 'SoftDeletes') === false) {
            // Add use statement
            $content = preg_replace('/namespace\s+[\w\\\\]+;\s*/', "$0\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;\n", $content);
            
            // Add trait inside class
            if (preg_match('/class\s+\w+\s+extends\s+\w+\s*\{(\s*)use\s+/', $content)) {
                $content = preg_replace('/(class\s+\w+\s+extends\s+\w+\s*\{(\s*)use\s+)/', "$1SoftDeletes, ", $content);
            } else {
                $content = preg_replace('/(class\s+\w+\s+extends\s+\w+\s*\{)/', "$1\n    use SoftDeletes;\n", $content);
            }
            $changed = true;
        }
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}
echo "Done.\n";
