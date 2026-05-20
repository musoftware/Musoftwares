<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');

$title = 'Snapchat Downloader';
$slug = 'snapdownloader';
$description = 'Download public stories, highlights, spotlights, and episodes directly to your local drive. Built for speed and simplicity without touching the cloud.';
$short_description = 'Snapchat Public Media Downloader';
$icon = 'Video';
$category = 'Media';
$runner_component = 'SnapDownloaderRunner';
$is_active = 1;
$is_free = 1;

// Check if already exists
$stmt = $pdo->prepare('SELECT id FROM tools WHERE slug = ?');
$stmt->execute([$slug]);
if ($stmt->fetch()) {
    $stmt = $pdo->prepare('UPDATE tools SET title=?, description=?, short_description=?, icon=?, category=?, runner_component=?, is_active=?, is_free=? WHERE slug=?');
    $stmt->execute([$title, $description, $short_description, $icon, $category, $runner_component, $is_active, $is_free, $slug]);
    echo "Tool updated in database successfully.\n";
} else {
    $stmt = $pdo->prepare('INSERT INTO tools (title, slug, description, short_description, icon, category, runner_component, is_active, is_free) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $slug, $description, $short_description, $icon, $category, $runner_component, $is_active, $is_free]);
    echo "Tool registered in database successfully.\n";
}
