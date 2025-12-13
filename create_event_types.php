<?php

echo "Creating event_type table and sample data...\n";

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=musehub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table
    $createTable = "
    CREATE TABLE IF NOT EXISTS event_type (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description VARCHAR(255),
        icon VARCHAR(50),
        color VARCHAR(7),
        capacity_type VARCHAR(20) NOT NULL DEFAULT 'unlimited',
        default_max_participants INT,
        typical_duration_hours INT,
        requires_payment TINYINT(1) NOT NULL DEFAULT 0,
        certificate_enabled TINYINT(1) NOT NULL DEFAULT 0,
        recording_enabled TINYINT(1) NOT NULL DEFAULT 0,
        allowed_location VARCHAR(20) NOT NULL DEFAULT 'both',
        visibility VARCHAR(20) NOT NULL DEFAULT 'public',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createTable);
    echo "✅ Table 'event_type' created\n";
    
    // Add column to event table
    $stmt = $pdo->query("SHOW COLUMNS FROM event LIKE 'event_type_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE event ADD COLUMN event_type_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE event ADD CONSTRAINT fk_event_event_type 
                    FOREIGN KEY (event_type_id) REFERENCES event_type(id) ON DELETE SET NULL");
        echo "✅ Column 'event_type_id' added to event table\n";
    }
    
    // Insert sample event types
    $eventTypes = [
        [
            'name' => 'Exhibition',
            'description' => 'Art exhibition and gallery showcase',
            'icon' => '🎨',
            'color' => '#FF6B6B',
            'capacity_type' => 'unlimited',
            'default_max_participants' => null,
            'typical_duration_hours' => 8,
            'requires_payment' => 0,
            'certificate_enabled' => 0,
            'recording_enabled' => 1,
            'allowed_location' => 'both',
            'visibility' => 'public',
            'sort_order' => 1
        ],
        [
            'name' => 'Workshop',
            'description' => 'Interactive art workshop and hands-on session',
            'icon' => '🛠️',
            'color' => '#4ECDC4',
            'capacity_type' => 'limited',
            'default_max_participants' => 20,
            'typical_duration_hours' => 3,
            'requires_payment' => 1,
            'certificate_enabled' => 1,
            'recording_enabled' => 1,
            'allowed_location' => 'both',
            'visibility' => 'public',
            'sort_order' => 2
        ],
        [
            'name' => 'Conference',
            'description' => 'Art conference and professional talks',
            'icon' => '🎤',
            'color' => '#95E1D3',
            'capacity_type' => 'limited',
            'default_max_participants' => 100,
            'typical_duration_hours' => 6,
            'requires_payment' => 1,
            'certificate_enabled' => 1,
            'recording_enabled' => 1,
            'allowed_location' => 'both',
            'visibility' => 'public',
            'sort_order' => 3
        ],
        [
            'name' => 'Auction',
            'description' => 'Art auction and bidding event',
            'icon' => '🔨',
            'color' => '#F38181',
            'capacity_type' => 'invite_only',
            'default_max_participants' => 50,
            'typical_duration_hours' => 4,
            'requires_payment' => 0,
            'certificate_enabled' => 0,
            'recording_enabled' => 1,
            'allowed_location' => 'both',
            'visibility' => 'private',
            'sort_order' => 4
        ],
        [
            'name' => 'Gallery Opening',
            'description' => 'Grand opening of new gallery or exhibition',
            'icon' => '🖼️',
            'color' => '#AA96DA',
            'capacity_type' => 'unlimited',
            'default_max_participants' => null,
            'typical_duration_hours' => 4,
            'requires_payment' => 0,
            'certificate_enabled' => 0,
            'recording_enabled' => 1,
            'allowed_location' => 'offline',
            'visibility' => 'public',
            'sort_order' => 5
        ],
        [
            'name' => 'Artist Talk',
            'description' => 'Meet the artist and Q&A session',
            'icon' => '💬',
            'color' => '#FCBAD3',
            'capacity_type' => 'limited',
            'default_max_participants' => 30,
            'typical_duration_hours' => 2,
            'requires_payment' => 0,
            'certificate_enabled' => 0,
            'recording_enabled' => 1,
            'allowed_location' => 'both',
            'visibility' => 'public',
            'sort_order' => 6
        ],
        [
            'name' => 'Virtual Tour',
            'description' => 'Online virtual gallery or museum tour',
            'icon' => '🌐',
            'color' => '#A8D8EA',
            'capacity_type' => 'unlimited',
            'default_max_participants' => null,
            'typical_duration_hours' => 1,
            'requires_payment' => 0,
            'certificate_enabled' => 0,
            'recording_enabled' => 1,
            'allowed_location' => 'online',
            'visibility' => 'public',
            'sort_order' => 7
        ],
        [
            'name' => 'Masterclass',
            'description' => 'Advanced art techniques masterclass',
            'icon' => '🎓',
            'color' => '#FFE66D',
            'capacity_type' => 'limited',
            'default_max_participants' => 15,
            'typical_duration_hours' => 6,
            'requires_payment' => 1,
            'certificate_enabled' => 1,
            'recording_enabled' => 1,
            'allowed_location' => 'both',
            'visibility' => 'members_only',
            'sort_order' => 8
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO event_type (
            name, description, icon, color, capacity_type, 
            default_max_participants, typical_duration_hours, requires_payment,
            certificate_enabled, recording_enabled, allowed_location, 
            visibility, sort_order
        ) VALUES (
            :name, :description, :icon, :color, :capacity_type,
            :default_max_participants, :typical_duration_hours, :requires_payment,
            :certificate_enabled, :recording_enabled, :allowed_location,
            :visibility, :sort_order
        )
    ");
    
    foreach ($eventTypes as $type) {
        try {
            $stmt->execute($type);
            echo "✅ Created event type: {$type['name']}\n";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "⏭️  Event type '{$type['name']}' already exists\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✅ Event types created successfully!\n";
    echo "\n📊 Summary:\n";
    echo "   - 8 creative event types added\n";
    echo "   - Free events: Exhibition, Gallery Opening, Artist Talk, Virtual Tour\n";
    echo "   - Paid events: Workshop, Conference, Masterclass\n";
    echo "   - Invite-only: Auction\n";
    echo "   - Certificate available: Workshop, Conference, Masterclass\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
