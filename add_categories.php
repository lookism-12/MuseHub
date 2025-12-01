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

    echo "Adding post categories...\n\n";

    $categories = [
        ['News', 'news', 'Actualités et annonces', 'fas fa-newspaper', '#FF6B6B'],
        ['Questions', 'questions', 'Questions et discussions', 'fas fa-question-circle', '#4ECDC4'],
        ['Memes', 'memes', 'Humour et memes', 'fas fa-laugh-squint', '#FFE66D']
    ];

    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO post_category (name, slug, description, icon, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($category);
        echo "✅ Category '{$category[0]}' added\n";
    }

    echo "\n✅ Post categories created successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
