<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
if ($container->has('doctrine.orm.entity_manager')) {
    $em = $container->get('doctrine.orm.entity_manager');
} else {
    $em = $container->get('doctrine')->getManager();
}

$repo = $em->getRepository(User::class);

echo "--- START VERIFICATION ---\n";

$allUsers = $repo->findAll();
$totalCount = count($allUsers);
echo "Total Users in DB: $totalCount\n";

// Test Fix Logic
$role = 'ROLE_USER';
$qb = $repo->createQueryBuilder('u');

if ($role && $role !== 'ROLE_USER') {
    $qb->where('u.roles LIKE :role')
       ->setParameter('role', '%' . $role . '%');
}

$users = $qb->getQuery()->getResult();
$fixCount = count($users);
echo "Users found with FIX logic: $fixCount\n";

if ($fixCount === $totalCount) {
    echo "SUCCESS: Fix logic returns all users.\n";
} else {
    echo "FAILURE: Fix logic returned $fixCount, expected $totalCount.\n";
}

echo "--- END VERIFICATION ---\n";
