<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Meilisearch\Client;

#[Route('/api/search')]
class SearchHealthController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository,
        private string $meilisearchUrl,
        private string $meilisearchApiKey
    ) {
    }

    /**
     * Health check endpoint to verify search indexing status
     */
    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        try {
            // Get database count
            $dbCount = $this->postRepository->count([]);

            // Get Meilisearch count
            $client = new Client($this->meilisearchUrl, $this->meilisearchApiKey);
            $index = $client->index('posts');
            $stats = $index->stats();
            $indexCount = $stats['numberOfDocuments'] ?? 0;
            $isIndexing = $stats['isIndexing'] ?? false;

            // Calculate missing posts
            $missing = $dbCount - $indexCount;
            $healthy = ($missing === 0);

            return $this->json([
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'database_posts' => $dbCount,
                'indexed_posts' => $indexCount,
                'missing_posts' => $missing,
                'is_indexing' => $isIndexing,
                'meilisearch_url' => $this->meilisearchUrl,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
