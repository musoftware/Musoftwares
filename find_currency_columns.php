<?php
$host = '127.0.0.1';
$db = 'newmusoftware';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'newmusoftware' AND COLUMN_NAME IN ('currency', 'fee_currency', 'converted_currency', 'reward_currency', 'spend_currency', 'spent_currency', 'plan_currency', 'booking_rate_currency', 'hour_rate_currency')");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
