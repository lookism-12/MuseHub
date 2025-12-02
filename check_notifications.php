<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection([
    'url' => 'mysql://root@127.0.0.1:3306/musehub'
]);

echo "=== Notifications en base ===\n\n";

$result = $conn->executeQuery('
    SELECT id, type, status, scheduled_at, created_at, sent_at 
    FROM event_notification 
    ORDER BY scheduled_at DESC 
    LIMIT 10
');

foreach ($result->fetchAllAssociative() as $row) {
    echo "ID: {$row['id']}\n";
    echo "Type: {$row['type']}\n";
    echo "Status: {$row['status']}\n";
    echo "Scheduled: {$row['scheduled_at']}\n";
    echo "Created: {$row['created_at']}\n";
    echo "Sent: " . ($row['sent_at'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\nDate actuelle PHP: " . (new DateTime())->format('Y-m-d H:i:s') . "\n";
echo "Date actuelle Immutable: " . (new DateTimeImmutable())->format('Y-m-d H:i:s') . "\n";
