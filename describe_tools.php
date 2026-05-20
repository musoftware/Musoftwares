<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');
$stmt = $pdo->query('DESCRIBE tools');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
