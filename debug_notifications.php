<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection([
    'url' => 'mysql://root@127.0.0.1:3306/musehub'
]);

$now = new DateTimeImmutable();
echo "Date actuelle: " . $now->format('Y-m-d H:i:s') . "\n\n";

echo "=== Requête comme dans le repository ===\n";
$result = $conn->executeQuery("
    SELECT * 
    FROM event_notification 
    WHERE status = 'pending' 
    AND scheduled_at <= ?
    ORDER BY scheduled_at ASC
", [$now->format('Y-m-d H:i:s')]);

echo "Notifications trouvées avec scheduled_at <= maintenant:\n";
foreach ($result->fetchAllAssociative() as $row) {
    echo "ID {$row['id']}: {$row['type']} - Planifié: {$row['scheduled_at']}\n";
}

echo "\n=== Toutes les notifications pending ===\n";
$result2 = $conn->executeQuery("
    SELECT id, type, status, scheduled_at 
    FROM event_notification 
    WHERE status = 'pending'
    ORDER BY scheduled_at ASC
");

foreach ($result2->fetchAllAssociative() as $row) {
    $scheduled = new DateTime($row['scheduled_at']);
    $diff = $now->getTimestamp() - $scheduled->getTimestamp();
    $isPast = $diff > 0 ? "PASSÉE" : "FUTURE";
    echo "ID {$row['id']}: {$row['type']} - {$row['scheduled_at']} ({$isPast})\n";
}
