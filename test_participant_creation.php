<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection([
    'url' => 'mysql://root@127.0.0.1:3306/musehub'
]);

echo "=== Vérification des participants et notifications ===\n\n";

// Trouver un événement futur
$events = $conn->executeQuery("
    SELECT id, uuid, title, date_time 
    FROM event 
    WHERE date_time > NOW() 
    ORDER BY date_time ASC 
    LIMIT 3
")->fetchAllAssociative();

echo "Événements futurs disponibles:\n";
foreach ($events as $event) {
    echo "  - ID {$event['id']}: {$event['title']} le {$event['date_time']}\n";
}

// Compter les participants
$participantCount = $conn->executeQuery("SELECT COUNT(*) as count FROM participant")->fetchAssociative();
echo "\nNombre total de participants: {$participantCount['count']}\n\n";

// Lister les participants récents
$participants = $conn->executeQuery("
    SELECT p.id, p.event_uuid, p.participant_uuid, p.status, p.created_at,
           e.title as event_title,
           u.email as user_email
    FROM participant p
    LEFT JOIN event e ON e.uuid = p.event_uuid
    LEFT JOIN user u ON u.uuid = p.participant_uuid
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAllAssociative();

echo "Derniers participants créés:\n";
foreach ($participants as $p) {
    echo "  - ID {$p['id']}: {$p['user_email']} -> {$p['event_title']} (créé le {$p['created_at']})\n";
    
    // Chercher les notifications pour ce participant
    $notifs = $conn->executeQuery("
        SELECT id, type, status, scheduled_at 
        FROM event_notification 
        WHERE event_id = (SELECT id FROM event WHERE uuid = ?) 
        AND user_id = (SELECT id FROM user WHERE uuid = ?)
    ", [$p['event_uuid'], $p['participant_uuid']])->fetchAllAssociative();
    
    if (empty($notifs)) {
        echo "    ⚠️  AUCUNE notification trouvée!\n";
    } else {
        foreach ($notifs as $n) {
            echo "    ✓ Notification ID {$n['id']}: {$n['type']} - {$n['status']} (planifiée: {$n['scheduled_at']})\n";
        }
    }
}

echo "\n=== Test: Créer un nouveau participant ===\n";

if (!empty($events)) {
    $testEvent = $events[0];
    $adminUser = $conn->executeQuery("SELECT id, uuid, email FROM user WHERE email = 'admin@musehub.com'")->fetchAssociative();
    
    if ($adminUser) {
        echo "Création d'un participant pour tester...\n";
        echo "Événement: {$testEvent['title']}\n";
        echo "Utilisateur: {$adminUser['email']}\n";
        
        // Vérifier s'il n'existe pas déjà
        $exists = $conn->executeQuery("
            SELECT COUNT(*) as count 
            FROM participant 
            WHERE event_uuid = ? AND participant_uuid = ?
        ", [$testEvent['uuid'], $adminUser['uuid']])->fetchAssociative();
        
        if ($exists['count'] > 0) {
            echo "⚠️  Ce participant existe déjà!\n";
        } else {
            $conn->executeStatement("
                INSERT INTO participant (event_uuid, participant_uuid, status, created_at) 
                VALUES (?, ?, 'confirmed', NOW())
            ", [$testEvent['uuid'], $adminUser['uuid']]);
            
            $participantId = $conn->lastInsertId();
            echo "✓ Participant créé avec ID: $participantId\n";
            echo "\nVérifiez maintenant si le subscriber a créé les notifications:\n";
            echo "Commande: php bin/console app:send-event-notifications\n";
        }
    }
}
