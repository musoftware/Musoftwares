<?php
$log = file_get_contents('C:\Users\Mahmo\.gemini\antigravity\brain\030c89ef-863f-426e-b756-1d7c4de1052b\.system_generated\tasks\task-770.log');

preg_match_all('/FAILED\s+(.*)\n(.*)\n(.*)\n/m', $log, $matches);

$unique = [];
foreach ($matches[0] as $match) {
    $unique[trim($match)] = ($unique[trim($match)] ?? 0) + 1;
}

arsort($unique);

foreach (array_slice($unique, 0, 20) as $err => $count) {
    echo "[$count times] $err\n\n";
}

