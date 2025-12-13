<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;

$container = require __DIR__ . '/config/bootstrap.php';
$em = $container->get(EntityManagerInterface::class);

// Créer un nouvel événement
$event = new Event();
$event->setTitle('Test iCalendar Export');
$event->setDescription('Événement de test pour l\'export iCalendar');
$event->setDateTime(new \DateTimeImmutable('2025-12-15 14:00:00'));
$event->setLocation('online');
$event->setOrganiserUuid('test-user-uuid');

$em->persist($event);
$em->flush();

echo "✅ Événement créé avec ID: " . $event->getId() . "\n";
echo "🔗 Testez avec: http://127.0.0.1:8001/api/events/" . $event->getId() . "/ics\n";
