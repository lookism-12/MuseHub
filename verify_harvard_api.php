<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Service\HarvardArtMuseumsService;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();

if ($container->has(HarvardArtMuseumsService::class)) {
    $service = $container->get(HarvardArtMuseumsService::class);
} else {
    // In case it's not public, we might need to get it differently or it might be private.
    // But services are usually public or autowired.
    // Let's try to get it from the private container if needed, but for now standard get.
    // Actually, services in Symfony 4+ are private by default.
    // We might need to make it public in services.yaml for this test script, or use a test kernel.
    // Alternatively, we can just instantiate it manually if we can get the http client.
    
    // Let's try to get it. If it fails, we'll see.
    try {
        $service = $container->get(HarvardArtMuseumsService::class);
    } catch (\Exception $e) {
        echo "Could not get service from container: " . $e->getMessage() . "\n";
        // Fallback: try to instantiate manually if possible, but HttpClient is hard to mock here without container.
        exit(1);
    }
}

echo "--- START HARVARD API VERIFICATION ---\n";

try {
    $artworks = $service->getArtworks();
    echo "Service call successful.\n";
    
    if (isset($artworks['records'])) {
        $count = count($artworks['records']);
        echo "Records found: $count\n";
        if ($count === 0) {
            echo "Note: 0 records found. Check if API Key is valid.\n";
        }
    } else {
        echo "Unexpected response structure: " . print_r($artworks, true) . "\n";
    }

} catch (\Exception $e) {
    echo "Error calling service: " . $e->getMessage() . "\n";
}

echo "--- END VERIFICATION ---\n";
