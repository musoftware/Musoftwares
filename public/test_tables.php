<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=oldmusoftware', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tables);
