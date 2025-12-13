<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostReaction;
use App\Repository\CommentRepository;
use App\Repository\PostReactionRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\ContentFilter;
use App\Service\NotificationService;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/posts')]
class CommunityApiController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository,
        private CommentRepository $commentRepository,
        private EntityManagerInterface $em,
        private ContentFilter $contentFilter,
        private UserRepository $userRepository,
        private PostReactionRepository $postReactionRepository,
        private NotificationService $notificationService,
        private SearchService $searchService,
        private $apiSearchLimiter = null,
        private $postReactionLimiter = null
    ) {
    }

    #[Route('', name: 'api_posts_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('category');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = min(50, max(1, (int)$request->query->get('limit', 20)));

        // Use regular database query with optional category filter
        $qb = $this->postRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($categoryId) {
            $qb->andWhere('p.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        $totalQb = $this->postRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)');
        if ($categoryId) {
            $totalQb->leftJoin('p.category', 'c')
                   ->andWhere('p.category = :categoryId')
                   ->setParameter('categoryId', $categoryId);
        }

        $total = (int)$totalQb->getQuery()->getSingleScalarResult();
        $posts = $qb->getQuery()->getResult();

        $data = array_map(function (Post $post) {
            return [
                'id' => $post->getId(),
                'author_uuid' => $post->getAuthorUuid(),
                'content' => $post->getContent(),
                'image_url' => $post->getImageUrl(),
                'likes_count' => $post->getLikesCount(),
                'dislikes_count' => $post->getDislikesCount(),
                'created_at' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                'comments_count' => $post->getComments()->count(),
            ];
        }, $posts);

        return new JsonResponse([
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'has_next' => (($page - 1) * $limit + count($posts)) < $total,
        ]);
    }

    #[Route('/search', name: 'api_posts_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $categoryId = $request->query->get('category');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = min(50, max(1, (int)$request->query->get('limit', 20)));

        if (empty($query)) {
            return new JsonResponse(['error' => 'Search query is required'], Response::HTTP_BAD_REQUEST);
        }

        return $this->searchPosts($query, $categoryId, $page, $limit);
    }

    #[Route('', name: 'api_posts_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['content'])) {
            return new JsonResponse(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        // Filter content
        $filterResult = $this->contentFilter->filterContent($data['content']);
        if (!$filterResult['isValid']) {
            return new JsonResponse([
                'error' => 'Content validation failed',
                'issues' => $filterResult['issues'],
            ], Response::HTTP_BAD_REQUEST);
        }

        $post = new Post();
        $post->setAuthorUuid($user->getUuid());
        $post->setContent($filterResult['filteredContent']);
        $post->setImageUrl($data['image_url'] ?? null);

        $this->em->persist($post);
        $this->em->flush();

        return new JsonResponse([
            'id' => $post->getId(),
            'message' => 'Post created successfully',
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/comments', name: 'api_posts_comments', methods: ['GET'])]
    public function getComments(int $id): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $comments = $post->getComments();
        $displayNames = $this->resolveUserNamesFromComments($comments->toArray());

        $data = array_map(function (Comment $comment) use ($displayNames) {
            $uuid = $comment->getCommenterUuid();
            return [
                'id' => $comment->getId(),
                'commenter_uuid' => $uuid,
                'commenter_display' => $displayNames[$uuid] ?? $this->formatGuestName($uuid),
                'content' => $comment->getContent(),
                'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $comments->toArray());

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'api_posts_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Only author or admin can update
        if ($post->getAuthorUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Not authorized to update this post'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            // Filter content
            $filterResult = $this->contentFilter->filterContent($data['content']);
            if (!$filterResult['isValid']) {
                return new JsonResponse([
                    'error' => 'Content validation failed',
                    'issues' => $filterResult['issues'],
                ], Response::HTTP_BAD_REQUEST);
            }
            $post->setContent($filterResult['filteredContent']);
        }

        if (isset($data['image_url'])) {
            $post->setImageUrl($data['image_url']);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $post->getId(),
            'message' => 'Post updated successfully',
        ]);
    }

    #[Route('/{id}', name: 'api_posts_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Only author or admin can delete
        if ($post->getAuthorUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Not authorized to delete this post'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($post);
        $this->em->flush();

        return new JsonResponse(['message' => 'Post deleted successfully']);
    }

    #[Route('/{id}/like', name: 'api_posts_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function like(int $id): JsonResponse
    {
        return $this->handleReaction($id, 'like');
    }

    #[Route('/{id}/dislike', name: 'api_posts_dislike', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function dislike(int $id): JsonResponse
    {
        return $this->handleReaction($id, 'dislike');
    }

    #[Route('/{id}/reaction', name: 'api_posts_reaction', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function react(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $type = $data['type'] ?? null;

        if (!in_array($type, ['like', 'dislike'], true)) {
            return new JsonResponse(['error' => 'Type de réaction invalide (like ou dislike attendu).'], Response::HTTP_BAD_REQUEST);
        }

        return $this->handleReaction($id, $type);
    }

    #[Route('/{id}/comments', name: 'api_posts_comment', methods: ['POST'])]
    public function comment(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['content'])) {
            return new JsonResponse(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        // Allow comments without authentication (public feature)
        $user = $this->getUser();
        $commenterUuid = $user ? $user->getUuid() : ($data['commenter_uuid'] ?? null);
        if (!$commenterUuid) {
            if (!empty($data['commenter_name'])) {
                $normalizedName = preg_replace('/[^a-z0-9]+/i', '_', $data['commenter_name']);
                $commenterUuid = 'guest_' . strtolower(trim($normalizedName, '_')) . '_' . uniqid();
            } else {
                $commenterUuid = 'guest_' . uniqid();
            }
        }

        // Filter content
        $filterResult = $this->contentFilter->filterContent($data['content']);
        if (!$filterResult['isValid']) {
            return new JsonResponse([
                'error' => 'Content validation failed',
                'issues' => $filterResult['issues'],
            ], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setCommenterUuid($commenterUuid);
        $comment->setContent($filterResult['filteredContent']);

        $this->em->persist($comment);
        $this->em->flush();

        // Create notification for post comment if user is authenticated
        if ($user) {
            $this->notificationService->createPostCommentNotification($post, $comment, $user->getUuid());
        }

        $displayName = $user ? ($user->getUsername() ?: $user->getEmail()) : ($data['commenter_name'] ?? $this->formatGuestName($commenterUuid));

        return new JsonResponse([
            'id' => $comment->getId(),
            'commenter_display' => $displayName,
            'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            'message' => 'Comment created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * @param array<int, Comment> $comments
     */
    private function resolveUserNamesFromComments(array $comments): array
    {
        $uuids = array_values(array_unique(array_filter(array_map(
            static fn(Comment $comment) => str_starts_with($comment->getCommenterUuid(), 'guest_') ? null : $comment->getCommenterUuid(),
            $comments
        ))));

        if (empty($uuids)) {
            return [];
        }

        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.uuid IN (:uuids)')
            ->setParameter('uuids', $uuids)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($users as $user) {
            $map[$user->getUuid()] = $user->getUsername() ?: $user->getEmail() ?: 'Membre MuseHub';
        }

        return $map;
    }

    private function formatGuestName(string $uuid): string
    {
        if (str_starts_with($uuid, 'guest_')) {
            return 'Invité';
        }

        return 'Utilisateur ' . substr($uuid, 0, 6);
    }

    private function handleReaction(int $postId, string $type): JsonResponse
    {
        // Debug logging
        error_log("handleReaction called: postId=$postId, type=$type");

        $post = $this->postRepository->find($postId);
        if (!$post) {
            error_log("Post not found: $postId");
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if (!$user) {
            error_log("User not authenticated");
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        error_log("User authenticated: " . $user->getUuid());

        $reaction = $this->postReactionRepository->findOneByPostAndUser($post, $user->getUuid());
        $status = 'created';

        if (!$reaction) {
            $reaction = new PostReaction();
            $reaction->setPost($post);
            $reaction->setUserUuid($user->getUuid());
            $reaction->setType($type);
            if ($type === 'like') {
                $post->incrementLikes();
            } else {
                $post->incrementDislikes();
            }
            $this->em->persist($reaction);
        } elseif ($reaction->getType() === $type) {
            $status = 'unchanged';
        } else {
            if ($reaction->getType() === 'like') {
                $post->decrementLikes();
            } else {
                $post->decrementDislikes();
            }

            if ($type === 'like') {
                $post->incrementLikes();
            } else {
                $post->incrementDislikes();
            }

            $reaction->setType($type);
            $status = 'updated';
        }

        $this->em->flush();

        // Create notification if this is a new reaction
        if ($status === 'created') {
            $this->notificationService->createPostReactionNotification($post, $user->getUuid(), $type);
        }

        return new JsonResponse([
            'id' => $post->getId(),
            'likes_count' => $post->getLikesCount(),
            'dislikes_count' => $post->getDislikesCount(),
            'active_reaction' => $reaction->getType(),
            'status' => $status,
            'message' => $status === 'unchanged'
                ? 'Réaction déjà enregistrée.'
                : 'Réaction mise à jour.',
        ]);
    }

    /**
     * Search posts using MeiliSearch
     */
    private function searchPosts(string $query, ?string $categoryId, int $page, int $limit): JsonResponse
    {

        $filters = [];
        if ($categoryId) {
            $filters['categoryId'] = $categoryId;
        }

        $searchResults = $this->searchService->searchPosts($query, $limit, $filters);

        if (isset($searchResults['error'])) {
            return new JsonResponse(['error' => 'Search service unavailable'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // Get the actual Post entities from the database using the IDs from search results
        $postIds = array_column($searchResults['hits'], 'id');
        if (empty($postIds)) {
            return new JsonResponse([
                'data' => [],
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'has_next' => false,
                'query' => $query,
            ]);
        }

                // Fetch posts with comments loaded for proper counting
                $posts = $this->postRepository->createQueryBuilder('p')
                    ->leftJoin('p.comments', 'c')
                    ->addSelect('COUNT(c.id) as commentCount')
                    ->where('p.id IN (:ids)')
                    ->setParameter('ids', $postIds)
                    ->groupBy('p.id')
                    ->orderBy('p.createdAt', 'DESC') // Order by creation date as fallback
                    ->getQuery()
                    ->getResult();

        // Create a map for quick lookup
        $postsMap = [];
        foreach ($posts as $post) {
            $postsMap[$post[0]->getId()] = $post[0];
        }

        // Build the response data maintaining the order from search results
        $data = [];
        foreach ($searchResults['hits'] as $hit) {
            $post = $postsMap[$hit['id']] ?? null;
            if (!$post) {
                continue;
            }

            $data[] = [
                'id' => $post->getId(),
                'author_uuid' => $post->getAuthorUuid(),
                'content' => $post->getContent(),
                'image_url' => $post->getImageUrl(),
                'likes_count' => $post->getLikesCount(),
                'dislikes_count' => $post->getDislikesCount(),
                'created_at' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                'comments_count' => $post->getComments()->count(),
            ];
        }

        return new JsonResponse([
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $searchResults['total'],
            'has_next' => count($data) === $limit && ($page * $limit) < $searchResults['total'],
            'query' => $query,
            'processing_time_ms' => $searchResults['processing_time_ms'] ?? null,
        ]);
    }
}
