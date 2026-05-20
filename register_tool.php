<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');

$title = 'Screenshot Feedback Workspace';
$slug = 'screenshot-feedback';
$description = 'A clean, operational workspace for UI review, feedback pins, and screenshot organization. Built for speed and simplicity.';
$short_description = 'UI Review & Feedback Tool';
$icon = 'ImageIcon';
$category = 'Productivity';
$runner_component = 'ScreenshotFeedbackRunner';
$is_active = 1;
$is_free = 1;

$stmt = $pdo->prepare('INSERT INTO tools (title, slug, description, short_description, icon, category, runner_component, is_active, is_free) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$title, $slug, $description, $short_description, $icon, $category, $runner_component, $is_active, $is_free]);
echo "Tool registered in database successfully.";
