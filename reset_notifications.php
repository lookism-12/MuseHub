<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection([
    'url' => 'mysql://root@127.0.0.1:3306/musehub'
]);

echo "=== Réinitialisation des notifications échouées ===\n\n";

// Réinitialiser le statut et la date
$pastTime = (new DateTimeImmutable())->modify('-2 minutes')->format('Y-m-d H:i:s');

$conn->executeStatement("
    UPDATE event_notification 
    SET status = 'pending', 
        scheduled_at = ?,
        error_message = NULL,
        retry_count = 0,
        sent_at = NULL
    WHERE id IN (3, 4)
", [$pastTime]);

echo "✓ Notifications 3 et 4 réinitialisées\n\n";

$result = $conn->executeQuery("
    SELECT id, type, status, scheduled_at 
    FROM event_notification 
    WHERE id IN (3, 4)
");

foreach ($result->fetchAllAssociative() as $row) {
    echo "ID {$row['id']}: {$row['status']} - Planifié: {$row['scheduled_at']}\n";
}

echo "\nExécutez maintenant: php bin/console app:send-event-notifications\n";
