<?php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

$dbHost = $_ENV['DATABASE_URL'] ?? 'mysql://root:@127.0.0.1:3306/musehub?serverVersion=8.0.32&charset=utf8mb4';

try {
    // Simple connection for root with no password
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=musehub;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check notification count
    $result = $pdo->query('SELECT COUNT(*) as count FROM notification');
    $count = $result->fetch()['count'];
    echo "Notifications in database: $count\n";

    if ($count > 0) {
        $result = $pdo->query('SELECT type, recipient_uuid, actor_uuid, is_read, created_at FROM notification ORDER BY created_at DESC LIMIT 5');
        echo "Recent notifications:\n";
        while ($row = $result->fetch()) {
            echo "- Type: {$row['type']}, Recipient: {$row['recipient_uuid']}, Actor: {$row['actor_uuid']}, Read: {$row['is_read']}, Created: {$row['created_at']}\n";
        }
    }

    // Check if there are any posts
    $result = $pdo->query('SELECT COUNT(*) as count FROM post');
    $postCount = $result->fetch()['count'];
    echo "Total posts: $postCount\n";

    // Check if there are any comments
    $result = $pdo->query('SELECT COUNT(*) as count FROM comment');
    $commentCount = $result->fetch()['count'];
    echo "Total comments: $commentCount\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
