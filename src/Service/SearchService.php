<?php

namespace App\Service;

use App\Entity\Post;
use Meilisearch\Client;
use Psr\Log\LoggerInterface;

class SearchService
{
    private Client $client;
    private string $indexName = 'posts';

    public function __construct(
        string $meilisearchUrl,
        string $meilisearchApiKey,
        private LoggerInterface $logger
    ) {
        $this->client = new Client($meilisearchUrl, $meilisearchApiKey);
        $this->ensureIndexExists();
    }

    /**
     * Ensure the posts index exists with proper configuration
     */
    private function ensureIndexExists(): void
    {
        try {
            $this->client->getIndex($this->indexName);
        } catch (\Exception $e) {
            // Index doesn't exist, create it
            $this->client->createIndex($this->indexName, ['primaryKey' => 'id']);

            // Configure searchable attributes
            $index = $this->client->index($this->indexName);
            $index->updateSearchableAttributes(['content', 'authorUuid']);
            $index->updateFilterableAttributes(['authorUuid', 'categoryId', 'createdAt']);
            $index->updateSortableAttributes(['createdAt', 'likesCount']);
        }
    }

    /**
     * Index a post in Meilisearch
     */
    public function indexPost(Post $post): void
    {
        try {
            $document = $this->postToDocument($post);
            $result = $this->client->index($this->indexName)->addDocuments([$document]);

            $this->logger->info('Post indexed in Meilisearch', [
                'post_id' => $post->getId(),
                'task_uid' => $result['taskUid'] ?? null,
                'status' => $result['status'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index post', [
                'post_id' => $post->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw to make failures visible in event listener
            throw $e;
        }
    }

    /**
     * Update an existing post in the index
     */
    public function updatePostIndex(Post $post): void
    {
        try {
            $document = $this->postToDocument($post);
            $result = $this->client->index($this->indexName)->updateDocuments([$document]);

            $this->logger->info('Post index updated in Meilisearch', [
                'post_id' => $post->getId(),
                'task_uid' => $result['taskUid'] ?? null,
                'status' => $result['status'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update post index', [
                'post_id' => $post->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw to make failures visible in event listener
            throw $e;
        }
    }

    /**
     * Delete a post from the index
     */
    public function deletePostIndex(int $postId): void
    {
        try {
            $result = $this->client->index($this->indexName)->deleteDocument($postId);

            $this->logger->info('Post deleted from Meilisearch index', [
                'post_id' => $postId,
                'task_uid' => $result['taskUid'] ?? null,
                'status' => $result['status'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete post from index', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw to make failures visible in event listener
            throw $e;
        }
    }

    /**
     * Search posts with ranking
     */
    public function searchPosts(string $query, int $limit = 20, array $filters = []): array
    {
        try {
            $searchParams = [
                'limit' => $limit,
            ];

            // Add filters if provided
            if (!empty($filters)) {
                $filterStrings = [];
                foreach ($filters as $key => $value) {
                    $filterStrings[] = "$key = '$value'";
                }
                $searchParams['filter'] = implode(' AND ', $filterStrings);
            }

            $results = $this->client->index($this->indexName)->search($query, $searchParams);

            return [
                'hits' => $results->getHits(),
                'total' => $results->getEstimatedTotalHits(),
                'query' => $query,
                'processing_time_ms' => $results->getProcessingTimeMs()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [
                'hits' => [],
                'total' => 0,
                'query' => $query,
                'error' => 'Search service unavailable'
            ];
        }
    }

    /**
     * Convert Post entity to Meilisearch document
     */
    private function postToDocument(Post $post): array
    {
        return [
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'authorUuid' => $post->getAuthorUuid(),
            'imageUrl' => $post->getImageUrl(),
            'categoryId' => $post->getCategory()?->getId(),
            'categoryName' => $post->getCategory()?->getName(),
            'createdAt' => $post->getCreatedAt()->getTimestamp(),
            'likesCount' => $post->getLikesCount(),
            'dislikesCount' => $post->getDislikesCount(),
        ];
    }
}
