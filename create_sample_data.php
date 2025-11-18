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

    echo "Creating sample data...\n\n";

    // Créer une catégorie
    $pdo->exec("INSERT IGNORE INTO category (id, name, description) VALUES (1, 'Peinture', 'Œuvres peintes')");
    echo "✅ Category created\n";

    // Créer des œuvres
    $artworks = [
        ['Titre de l\'œuvre 1', 'Description de la première œuvre', 'https://via.placeholder.com/400x300?text=Artwork+1', '250.00'],
        ['Titre de l\'œuvre 2', 'Description de la deuxième œuvre', 'https://via.placeholder.com/400x300?text=Artwork+2', '350.00'],
        ['Titre de l\'œuvre 3', 'Description de la troisième œuvre', 'https://via.placeholder.com/400x300?text=Artwork+3', '450.00'],
    ];

    $adminUuid = $pdo->query("SELECT uuid FROM user WHERE email = 'admin@musehub.com' LIMIT 1")->fetchColumn();

    foreach ($artworks as $artwork) {
        $stmt = $pdo->prepare("INSERT INTO artwork (title, description, image_url, price, artist_uuid, status, category_id) VALUES (?, ?, ?, ?, ?, 'visible', 1)");
        $stmt->execute([$artwork[0], $artwork[1], $artwork[2], $artwork[3], $adminUuid]);
    }
    echo "✅ Artworks created\n";

    // Créer un événement
    $futureDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    $stmt = $pdo->prepare("INSERT INTO event (uuid, title, description, date_time, location, organiser_uuid, is_active, created_at) VALUES (UUID(), ?, ?, ?, 'online', ?, 1, NOW())");
    $stmt->execute(['Exposition d\'art moderne', 'Une exposition exceptionnelle d\'art contemporain', $futureDate, $adminUuid]);
    echo "✅ Event created\n";

    // Créer un post
    $stmt = $pdo->prepare("INSERT INTO post (author_uuid, content, likes_count, created_at) VALUES (?, ?, 5, NOW())");
    $stmt->execute([$adminUuid, 'Bienvenue sur MuseHub ! Partagez vos créations et découvrez celles des autres artistes.']);
    echo "✅ Post created\n";

    // Créer un listing marketplace
    $artworkId = $pdo->query("SELECT id FROM artwork LIMIT 1")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO listing (uuid, artwork_uuid, price, stock, status, created_at) VALUES (UUID(), (SELECT CONCAT('artwork-', id) FROM artwork LIMIT 1), '250.00', 1, 'available', NOW())");
    $stmt->execute();
    echo "✅ Listing created\n";

    echo "\n✅ Sample data created successfully!\n";
    echo "Visit http://localhost:8000 to see the dynamic pages!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}


