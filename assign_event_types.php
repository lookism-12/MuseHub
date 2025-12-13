<?php

echo "🔗 Assignation des types aux événements existants...\n\n";

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=musehub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les événements
    $stmt = $pdo->query("SELECT id, title, location FROM event WHERE event_type_id IS NULL");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "✅ Tous les événements ont déjà un type assigné!\n";
        exit(0);
    }
    
    // Récupérer les types d'événements
    $types = $pdo->query("SELECT id, name FROM event_type ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($types)) {
        echo "❌ Aucun type d'événement trouvé. Exécutez d'abord create_event_types.php\n";
        exit(1);
    }
    
    echo "📊 Trouvé " . count($events) . " événement(s) sans type\n";
    echo "📋 Types disponibles:\n";
    foreach ($types as $type) {
        echo "   {$type['id']}. {$type['name']}\n";
    }
    echo "\n";
    
    // Assignation intelligente basée sur le titre ou la localisation
    $updateStmt = $pdo->prepare("UPDATE event SET event_type_id = ? WHERE id = ?");
    
    foreach ($events as $event) {
        $title = strtolower($event['title']);
        $typeId = 1; // Exhibition par défaut
        
        // Logique d'assignation intelligente
        if (stripos($title, 'workshop') !== false || stripos($title, 'atelier') !== false) {
            $typeId = 2; // Workshop
        } elseif (stripos($title, 'conference') !== false || stripos($title, 'conférence') !== false) {
            $typeId = 3; // Conference
        } elseif (stripos($title, 'auction') !== false || stripos($title, 'vente') !== false) {
            $typeId = 4; // Auction
        } elseif (stripos($title, 'opening') !== false || stripos($title, 'vernissage') !== false) {
            $typeId = 5; // Gallery Opening
        } elseif (stripos($title, 'talk') !== false || stripos($title, 'rencontre') !== false) {
            $typeId = 6; // Artist Talk
        } elseif (stripos($title, 'virtual') !== false || stripos($title, 'virtuel') !== false || $event['location'] === 'online') {
            $typeId = 7; // Virtual Tour
        } elseif (stripos($title, 'masterclass') !== false || stripos($title, 'master class') !== false) {
            $typeId = 8; // Masterclass
        }
        
        $updateStmt->execute([$typeId, $event['id']]);
        
        // Récupérer le nom du type
        $typeName = array_filter($types, fn($t) => $t['id'] == $typeId);
        $typeName = reset($typeName)['name'];
        
        echo "✅ '{$event['title']}' → {$typeName}\n";
    }
    
    echo "\n✅ Tous les événements ont été mis à jour avec des types!\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
