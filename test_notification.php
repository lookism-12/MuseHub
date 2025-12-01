<?php
require 'vendor/autoload.php';

use App\Service\NotificationService;
use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$notificationService = $container->get(NotificationService::class);
$postRepository = $container->get(PostRepository::class);
$em = $container->get(EntityManagerInterface::class);

// Get the first post
$posts = $postRepository->findAll();
if (empty($posts)) {
    echo "No posts found to test notifications\n";
    exit;
}

$post = $posts[0];
echo "Testing notification for post ID: " . $post->getId() . "\n";
echo "Post author UUID: " . $post->getAuthorUuid() . "\n";

// Create a test notification
$notification = $notificationService->createPostReactionNotification($post, 'test-actor-uuid', 'like');

if ($notification) {
    echo "Notification created successfully!\n";
    echo "Type: " . $notification->getType() . "\n";
    echo "Recipient: " . $notification->getRecipientUuid() . "\n";
    echo "Actor: " . $notification->getActorUuid() . "\n";
    echo "Post ID: " . $notification->getPostId() . "\n";

    $em->flush();
} else {
    echo "Notification was not created (probably because actor = recipient)\n";
}

echo "Done testing notifications\n";
