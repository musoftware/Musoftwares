<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');

$title = 'IPTV Downloader & Recorder';
$slug = 'iptv-downloader';
$description = 'A professional-grade operational workspace for loading IPTV playlists, category-browsing channels, and recording live streams or downloading VOD assets locally.';
$short_description = 'IPTV Playlist Manager & Stream Recorder';
$icon = 'Tv';
$category = 'Media';
$runner_component = 'IPTVDownloaderRunner';
$is_active = 1;
$is_free = 1;

// Check if tool already exists
$check = $pdo->prepare('SELECT id FROM tools WHERE slug = ?');
$check->execute([$slug]);
if ($check->fetch()) {
    echo "Tool already registered.\n";
    exit;
}

$stmt = $pdo->prepare('INSERT INTO tools (title, slug, description, short_description, icon, category, runner_component, is_active, is_free) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$title, $slug, $description, $short_description, $icon, $category, $runner_component, $is_active, $is_free]);
echo "Tool registered in database successfully.\n";
