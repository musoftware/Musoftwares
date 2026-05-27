<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=newmusoftware', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'workspace_settings'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN workspace_settings JSON NULL AFTER remember_token");
        echo "Column workspace_settings added successfully.\n";
    } else {
        echo "Column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
