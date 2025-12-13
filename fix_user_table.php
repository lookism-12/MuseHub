<?php

echo "Fixing user table...\n";

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=musehub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM user LIKE 'password_reset_token'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE user ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL");
        echo "✅ Column 'password_reset_token' added\n";
    } else {
        echo "⏭️  Column 'password_reset_token' already exists\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM user LIKE 'password_reset_requested_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE user ADD COLUMN password_reset_requested_at DATETIME DEFAULT NULL");
        echo "✅ Column 'password_reset_requested_at' added\n";
    } else {
        echo "⏭️  Column 'password_reset_requested_at' already exists\n";
    }
    
    echo "\n✅ User table fixed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
