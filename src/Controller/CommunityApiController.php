<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\ContentFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/posts')]
class CommunityApiController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository,
        private CommentRepository $commentRepository,
        private EntityManagerInterface $em,
        private ContentFilter $contentFilter
    ) {
    }

    #[Route('', name: 'api_posts_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = min(50, max(1, (int)$request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $posts = $this->postRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = array_map(function (Post $post) {
            return [
                'id' => $post->getId(),
                'author_uuid' => $post->getAuthorUuid(),
                'content' => $post->getContent(),
                'image_url' => $post->getImageUrl(),
                'likes_count' => $post->getLikesCount(),
                'created_at' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                'comments_count' => $post->getComments()->count(),
            ];
        }, $posts);

        return new JsonResponse([
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
        ]);
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

        $data = array_map(function (Comment $comment) {
            return [
                'id' => $comment->getId(),
                'commenter_uuid' => $comment->getCommenterUuid(),
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
        $post = $this->postRepository->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $post->incrementLikes();
        $this->em->flush();

        return new JsonResponse([
            'id' => $post->getId(),
            'likes_count' => $post->getLikesCount(),
            'message' => 'Post liked successfully',
        ]);
    }

    #[Route('/{id}/comments', name: 'api_posts_comment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function comment(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

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

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setCommenterUuid($user->getUuid());
        $comment->setContent($filterResult['filteredContent']);

        $this->em->persist($comment);
        $this->em->flush();

        return new JsonResponse([
            'id' => $comment->getId(),
            'message' => 'Comment created successfully',
        ], Response::HTTP_CREATED);
    }
}

