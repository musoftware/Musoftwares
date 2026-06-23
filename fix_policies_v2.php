<?php
$files = glob(__DIR__ . '/app/Policies/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, '$ability->user_id') !== false) {
        // Read file line by line to keep track of the last seen method parameter
        $lines = file($file);
        $paramName = null;
        foreach ($lines as &$line) {
            if (preg_match('/public function \w+\(\s*User\s+\$user\s*,\s*\w+\s+\$(\w+)\s*\)/', $line, $m)) {
                $paramName = '$' . $m[1];
            }
            if (strpos($line, '$ability->user_id') !== false && $paramName) {
                $line = str_replace('$ability->user_id', $paramName . '->user_id', $line);
            }
        }
        file_put_contents($file, implode("", $lines));
        echo "Fixed " . basename($file) . "\n";
    }
}
