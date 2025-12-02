<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Boot Kernel
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

// Get Container (use test.service_container if available, or just kernel container)
// In recent Symfony versions, private services are not accessible from container directly.
// We might need to use a trick or just get the EntityManager if it's public.
// EntityManagerInterface is usually aliased to 'doctrine.orm.entity_manager' which is public.

$container = $kernel->getContainer();

// Try to get EntityManager
if ($container->has('doctrine.orm.entity_manager')) {
    $em = $container->get('doctrine.orm.entity_manager');
} elseif ($container->has(EntityManagerInterface::class)) {
    $em = $container->get(EntityManagerInterface::class);
} else {
    // Fallback for newer Symfony where container is compiled
    // We can try to boot with debug=true which might make services public, or use a command.
    echo "Could not get EntityManager directly. Trying via doctrine service.\n";
    $doctrine = $container->get('doctrine');
    $em = $doctrine->getManager();
}

$repo = $em->getRepository(User::class);

// Dump all users and their roles
echo "Running test V3...\n";
echo "All Users:\n";
$allUsers = $repo->findAll();
foreach ($allUsers as $u) {
    // We can't easily see raw DB value via getter, but we can infer.
    // If we search for ROLE_USER and don't find them, but they exist, that's the bug.
    echo " - " . $u->getEmail() . " (Roles: " . implode(', ', $u->getRoles()) . ")\n";
}

// Test Search for ROLE_USER with FIX
$role = 'ROLE_USER';
echo "\nSearching for '$role' (with fix logic)...\n";
$qb = $repo->createQueryBuilder('u');

// FIX: Skip filter if role is ROLE_USER
if ($role && $role !== 'ROLE_USER') {
    $qb->where('u.roles LIKE :role')
       ->setParameter('role', '%' . $role . '%');
}

$users = $qb->getQuery()->getResult();
echo "Found " . count($users) . " users with '$role' (fix applied)\n";

$users = $qb->getQuery()->getResult();
echo "Found " . count($users) . " users with '$role'\n";
foreach ($users as $u) {
    echo " - " . $u->getEmail() . "\n";
}

if (count($users) < count($allUsers)) {
    echo "\nPOTENTIAL BUG: Found fewer users with ROLE_USER than total users.\n";
    echo "This implies some users have implicit ROLE_USER but are not matched by DB query.\n";
}


