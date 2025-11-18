<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

$dbHost = $_ENV['DATABASE_URL'] ?? 'mysql://root:@127.0.0.1:3306/musehub';
preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^?]+)/', $dbHost, $matches);

$host = $matches[3] ?? '127.0.0.1';
$port = $matches[4] ?? '3306';
$dbname = $matches[5] ?? 'musehub';
$user = $matches[1] ?? 'root';
$pass = $matches[2] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier et ajouter les colonnes si nécessaire
    $columns = $pdo->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('uuid', $columns)) {
        $pdo->exec("ALTER TABLE user ADD COLUMN uuid VARCHAR(36) UNIQUE AFTER id");
    }
    if (!in_array('roles', $columns)) {
        $pdo->exec("ALTER TABLE user ADD COLUMN roles JSON DEFAULT ('[]')");
    }
    if (!in_array('bio', $columns)) {
        $pdo->exec("ALTER TABLE user ADD COLUMN bio TEXT");
    }
    if (!in_array('avatar_url', $columns)) {
        $pdo->exec("ALTER TABLE user ADD COLUMN avatar_url VARCHAR(255)");
    }
    if (!in_array('is_active', $columns)) {
        $pdo->exec("ALTER TABLE user ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }

    // Vérifier si admin existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
    $stmt->execute(['admin@musehub.com']);
    if ($stmt->fetchColumn() > 0) {
        echo "✅ Admin user already exists!\n";
        echo "Email: admin@musehub.com\n";
        echo "Password: admin123\n";
        exit(0);
    }

    // Générer UUID
    $uuid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    // Hasher le mot de passe
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insérer l'admin
    $stmt = $pdo->prepare("
        INSERT INTO user (uuid, email, password, username, roles, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $uuid,
        'admin@musehub.com',
        $hashedPassword,
        'admin',
        json_encode(['ROLE_ADMIN']),
        1
    ]);

    echo "✅ Admin user created successfully!\n\n";
    echo "📧 Email: admin@musehub.com\n";
    echo "🔑 Password: admin123\n";
    echo "👤 Username: admin\n";
    echo "🔐 Role: ROLE_ADMIN\n\n";
    echo "🌐 Login at: http://localhost:8000/login\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}


