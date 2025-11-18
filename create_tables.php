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

    echo "Creating missing tables...\n\n";

    // Table listing
    if (!tableExists($pdo, 'listing')) {
        $pdo->exec("CREATE TABLE listing (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            artwork_uuid VARCHAR(36) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            stock INT NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'available',
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_artwork_uuid (artwork_uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'listing' created\n";
    } else {
        echo "⏭️  Table 'listing' already exists\n";
    }

    // Table transaction
    if (!tableExists($pdo, 'transaction')) {
        $pdo->exec("CREATE TABLE transaction (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            buyer_uuid VARCHAR(36) NOT NULL,
            listing_uuid VARCHAR(36) NOT NULL,
            amount NUMERIC(10, 2) NOT NULL,
            date DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'paid',
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_buyer_uuid (buyer_uuid),
            INDEX idx_listing_uuid (listing_uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'transaction' created\n";
    } else {
        echo "⏭️  Table 'transaction' already exists\n";
    }

    // Table event
    if (!tableExists($pdo, 'event')) {
        $pdo->exec("CREATE TABLE event (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            date_time DATETIME NOT NULL,
            location VARCHAR(50) NOT NULL,
            organiser_uuid VARCHAR(36) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_organiser_uuid (organiser_uuid),
            INDEX idx_date_time (date_time)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'event' created\n";
    } else {
        echo "⏭️  Table 'event' already exists\n";
    }

    // Table participant
    if (!tableExists($pdo, 'participant')) {
        $pdo->exec("CREATE TABLE participant (
            id INT AUTO_INCREMENT NOT NULL,
            event_uuid VARCHAR(36) NOT NULL,
            participant_uuid VARCHAR(36) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_event_uuid (event_uuid),
            INDEX idx_participant_uuid (participant_uuid),
            UNIQUE KEY unique_participation (event_uuid, participant_uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'participant' created\n";
    } else {
        echo "⏭️  Table 'participant' already exists\n";
    }

    // Table artwork (si elle n'existe pas)
    if (!tableExists($pdo, 'artwork')) {
        $pdo->exec("CREATE TABLE artwork (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            price NUMERIC(10, 2),
            artist_uuid VARCHAR(255) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'visible',
            category_id INT,
            PRIMARY KEY(id),
            INDEX idx_category_id (category_id),
            INDEX idx_artist_uuid (artist_uuid),
            FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'artwork' created\n";
    } else {
        echo "⏭️  Table 'artwork' already exists\n";
    }

    // Table category (si elle n'existe pas)
    if (!tableExists($pdo, 'category')) {
        $pdo->exec("CREATE TABLE category (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'category' created\n";
    } else {
        echo "⏭️  Table 'category' already exists\n";
    }

    // Table post (si elle n'existe pas)
    if (!tableExists($pdo, 'post')) {
        $pdo->exec("CREATE TABLE post (
            id INT AUTO_INCREMENT NOT NULL,
            author_uuid VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            image_url VARCHAR(255),
            likes_count INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_author_uuid (author_uuid),
            INDEX idx_created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'post' created\n";
    } else {
        echo "⏭️  Table 'post' already exists\n";
    }

    // Table comment (si elle n'existe pas)
    if (!tableExists($pdo, 'comment')) {
        $pdo->exec("CREATE TABLE comment (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            commenter_uuid VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_post_id (post_id),
            INDEX idx_commenter_uuid (commenter_uuid),
            FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        echo "✅ Table 'comment' created\n";
    } else {
        echo "⏭️  Table 'comment' already exists\n";
    }

    echo "\n✅ All tables created successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function tableExists($pdo, $tableName) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}


