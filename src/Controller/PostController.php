<?php
namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Service\SearchService;
use App\Service\ImageUploadService;
use App\Service\ReputationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/posts')]
class PostController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepository,
        private SearchService $searchService,
        private ImageUploadService $imageUploadService,
        private ReputationService $reputationService
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $posts = $this->postRepository->findBy([], ['createdAt' => 'DESC'], 20);

        $data = array_map(fn(Post $post) => [
            'id' => $post->getId(),
            'author_uuid' => $post->getAuthorUuid(),
            'content' => $post->getContent(),
            'image_url' => $post->getImageUrl(),
            'created_at' => $post->getCreatedAt()->format('c'),
            'likes_count' => $post->getLikesCount(),
            'dislikes_count' => $post->getDislikesCount(),
        ], $posts);

        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['content'])) {
            return $this->json(['error' => 'content is required'], 400);
        }

        $post = new Post();
        $post->setAuthorUuid($user->getUuid());
        $post->setContent($data['content']);
        $post->setImageUrl($data['image_url'] ?? null);

        $this->em->persist($post);
        $this->em->flush();

        // Award reputation for creating post
        $this->reputationService->awardPostCreation($user->getUuid());

        // Index post in Meilisearch
        $this->searchService->indexPost($post);

        return $this->json(['id' => $post->getId()], 201);
    }

    #[Route('/{id}', name: 'api_posts_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $user = $this->getUser();
        // Only author or admin can update
        if ($post->getAuthorUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Not authorized to update this post'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $post->setContent($data['content']);
        }
        if (isset($data['image_url'])) {
            $post->setImageUrl($data['image_url']);
        }

        $this->em->flush();

        // Update post in Meilisearch index
        $this->searchService->updatePostIndex($post);

        return $this->json(['id' => $post->getId(), 'message' => 'Post updated successfully']);
    }

    #[Route('/{id}', name: 'api_posts_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $user = $this->getUser();
        // Only author or admin can delete
        if ($post->getAuthorUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Not authorized to delete this post'], 403);
        }

        // Delete from Meilisearch index
        $this->searchService->deletePostIndex($id);

        $this->em->remove($post);
        $this->em->flush();

        return $this->json(['message' => 'Post deleted successfully']);
    }

    #[Route('/{id}/comments', methods: ['POST'])]
    public function comment(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['content'])) {
            return $this->json(['error' => 'content is required'], 400);
        }

        $commenterUuid = $data['commenter_uuid'] ?? null;
        if (!$commenterUuid) {
            if (!empty($data['commenter_name'])) {
                $normalizedName = preg_replace('/[^a-z0-9]+/i', '_', $data['commenter_name']);
                $commenterUuid = 'guest_' . strtolower(trim($normalizedName, '_')) . '_' . uniqid();
            } else {
                $commenterUuid = 'guest_' . uniqid();
            }
        }

        $comment = new \App\Entity\Comment();
        $comment->setPost($post);
        $comment->setCommenterUuid($commenterUuid);
        $comment->setContent($data['content']);

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json(['id' => $comment->getId()], 201);
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        if (empty($query)) {
            return $this->json(['error' => 'query parameter is required'], 400);
        }

        $limit = (int) $request->query->get('limit', 20);
        $limit = min(max($limit, 1), 100); // Between 1 and 100

        $results = $this->searchService->searchPosts($query, $limit);

        return $this->json($results);
    }

    #[Route('/{id}/upload-image', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadImage(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $user = $this->getUser();
        // Only author or admin can upload image
        if ($post->getAuthorUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Not authorized to upload image for this post'], 403);
        }

        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['error' => 'No image file provided'], 400);
        }

        try {
            $imageUrl = $this->imageUploadService->uploadPostImage($file);
            $post->setImageUrl($imageUrl);
            $this->em->flush();

            // Update search index with new image
            $this->searchService->updatePostIndex($post);

            return $this->json([
                'message' => 'Image uploaded successfully',
                'image_url' => $imageUrl,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
