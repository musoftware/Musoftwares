<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');

$title = 'Email Sender Pro';
$slug = 'email-sender';
$description = 'Send bulk emails locally with high deliverability using SMTP accounts and beautiful templates. Complete with bounce tracking and reporting.';
$short_description = 'Bulk Email Campaigns & Marketing';
$icon = 'Mail';
$category = 'Marketing';
$runner_component = 'EmailSenderRunner';
$is_active = 1;
$is_free = 1;

// Check if tool exists
$stmt = $pdo->prepare('SELECT id FROM tools WHERE slug = ?');
$stmt->execute([$slug]);
$tool = $stmt->fetch();

if ($tool) {
    $stmt = $pdo->prepare('UPDATE tools SET title=?, description=?, short_description=?, icon=?, category=?, runner_component=?, is_active=?, is_free=? WHERE slug=?');
    $stmt->execute([$title, $description, $short_description, $icon, $category, $runner_component, $is_active, $is_free, $slug]);
    echo "Tool updated successfully.";
} else {
    $stmt = $pdo->prepare('INSERT INTO tools (title, slug, description, short_description, icon, category, runner_component, is_active, is_free) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $slug, $description, $short_description, $icon, $category, $runner_component, $is_active, $is_free]);
    echo "Tool registered successfully.";
}
