<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');
$stmt = $pdo->query('SELECT COUNT(*) FROM countries');
echo $stmt->fetchColumn();
