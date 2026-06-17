<?php
$array = [
    'guid' => 'ef4539db-2749-4df3-9dab-2f737a7200c9',
    'title' => 'IPTV Downloader',
    'slug' => 'iptv-downloader',
];
$tool = (object) $array;

try {
    $runtime = $tool->metadata['runtime'] ?? 'nodejs';
    echo "Runtime: " . $runtime . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
