<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=newmusoftware', 'root', '');
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('TRUNCATE TABLE tool_downloads');
$pdo->exec('TRUNCATE TABLE tools');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Tools truncated successfully.";
