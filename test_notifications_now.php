<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection([
    'url' => 'mysql://root@127.0.0.1:3306/musehub'
]);

echo "=== Mise à jour des notifications pour test ===\n\n";

// Mettre les notifications 3 et 4 dans le passé (il y a 5 minutes)
$pastTime = (new DateTimeImmutable())->modify('-5 minutes')->format('Y-m-d H:i:s');

$conn->executeStatement("
    UPDATE event_notification 
    SET scheduled_at = ? 
    WHERE id IN (3, 4)
", [$pastTime]);

echo "✓ Notifications 3 et 4 mises à jour pour: $pastTime\n\n";

echo "=== Vérification ===\n";
$result = $conn->executeQuery("
    SELECT id, type, scheduled_at, status 
    FROM event_notification 
    WHERE id IN (3, 4)
");

foreach ($result->fetchAllAssociative() as $row) {
    echo "ID {$row['id']}: {$row['type']} - {$row['scheduled_at']} ({$row['status']})\n";
}

echo "\nMaintenant, exécutez: php bin/console app:send-event-notifications\n";
