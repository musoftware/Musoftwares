<?php
$env = parse_ini_file('.env');
$pdo = new PDO('mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD']);
$stmt = $pdo->query("SHOW COLUMNS FROM campaigns");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
