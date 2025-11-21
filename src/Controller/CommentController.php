<?php
// src/Controller/CommentController.php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\ContentFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/posts/{postId}/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepository,
        private CommentRepository $commentRepository,
        private ContentFilter $contentFilter
    ) {
    }

    #[Route('', name: 'create_comment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(int $postId, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['content'])) {
            return $this->json(['error' => 'content is required'], Response::HTTP_BAD_REQUEST);
        }

        // Filter content
        $filterResult = $this->contentFilter->filterContent($data['content']);
        if (!$filterResult['isValid']) {
            return $this->json([
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

        return $this->json([
            'id' => $comment->getId(),
            'message' => 'Comment created successfully'
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update_comment', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $postId, int $id, Request $request): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify comment belongs to the post
        if ($comment->getPost()->getId() !== $postId) {
            return $this->json(['error' => 'Comment does not belong to this post'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        // Only commenter or admin can update
        if ($comment->getCommenterUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Not authorized to update this comment'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            // Filter content
            $filterResult = $this->contentFilter->filterContent($data['content']);
            if (!$filterResult['isValid']) {
                return $this->json([
                    'error' => 'Content validation failed',
                    'issues' => $filterResult['issues'],
                ], Response::HTTP_BAD_REQUEST);
            }
            $comment->setContent($filterResult['filteredContent']);
        }

        $this->em->flush();

        return $this->json([
            'id' => $comment->getId(),
            'message' => 'Comment updated successfully'
        ]);
    }

    #[Route('/{id}', name: 'delete_comment', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $postId, int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify comment belongs to the post
        if ($comment->getPost()->getId() !== $postId) {
            return $this->json(['error' => 'Comment does not belong to this post'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        // Only commenter or admin can delete
        if ($comment->getCommenterUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Not authorized to delete this comment'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($comment);
        $this->em->flush();

        return $this->json(['message' => 'Comment deleted successfully']);
    }
}
