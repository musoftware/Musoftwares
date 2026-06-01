<?php
$content = file_get_contents('pest_results.txt');
$content = str_replace("\0", '', $content);

preg_match_all('/FAILED\s+.*\n\s+(.*)\n/i', $content, $matches);

$errs = [];
foreach($matches[1] as $err) {
    $err = trim($err);
    if (str_starts_with($err, 'Exception')) continue;
    
    // Normalize SQL queries
    $err = preg_replace('/\(Connection: mysql.*\)/', '(Connection: mysql...)', $err);
    $err = preg_replace('/values \(.*\)/', 'values (...)', $err);
    
    $errs[$err] = ($errs[$err] ?? 0) + 1;
}
arsort($errs);
foreach (array_slice($errs, 0, 30) as $k => $v) {
    echo "[$v times] $k\n";
}
