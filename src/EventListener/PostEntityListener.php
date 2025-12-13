<?php

namespace App\EventListener;

use App\Entity\Post;
use App\Service\SearchService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Doctrine Event Listener for automatic post indexing in Meilisearch
 * 
 * This listener automatically indexes posts in Meilisearch whenever they are:
 * - Created (postPersist)
 * - Updated (postUpdate)
 * - Deleted (preRemove)
 * 
 * This ensures ALL posts are searchable, regardless of how they're created
 * (API, migrations, direct Doctrine calls, etc.)
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class PostEntityListener
{
    public function __construct(
        private SearchService $searchService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Called after a new post is persisted to the database
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only handle Post entities
        if (!$entity instanceof Post) {
            return;
        }

        try {
            $this->logger->info('Auto-indexing new post in Meilisearch', [
                'post_id' => $entity->getId(),
                'event' => 'postPersist'
            ]);

            $this->searchService->indexPost($entity);

            $this->logger->info('Post successfully indexed', [
                'post_id' => $entity->getId()
            ]);
        } catch (\Exception $e) {
            // Log the error but don't throw - we don't want to break post creation
            // if Meilisearch is temporarily unavailable
            $this->logger->error('Failed to auto-index post in Meilisearch', [
                'post_id' => $entity->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Called after a post is updated in the database
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only handle Post entities
        if (!$entity instanceof Post) {
            return;
        }

        try {
            $this->logger->info('Auto-updating post in Meilisearch index', [
                'post_id' => $entity->getId(),
                'event' => 'postUpdate'
            ]);

            $this->searchService->updatePostIndex($entity);

            $this->logger->info('Post index successfully updated', [
                'post_id' => $entity->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to auto-update post in Meilisearch', [
                'post_id' => $entity->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Called before a post is removed from the database
     */
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        // Only handle Post entities
        if (!$entity instanceof Post) {
            return;
        }

        try {
            $this->logger->info('Auto-removing post from Meilisearch index', [
                'post_id' => $entity->getId(),
                'event' => 'preRemove'
            ]);

            $this->searchService->deletePostIndex($entity->getId());

            $this->logger->info('Post successfully removed from index', [
                'post_id' => $entity->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to auto-remove post from Meilisearch', [
                'post_id' => $entity->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
