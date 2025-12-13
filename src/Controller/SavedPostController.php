<?php

namespace App\Controller;

use App\Entity\SavedPost;
use App\Repository\PostRepository;
use App\Repository\SavedPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/saved-posts')]
#[IsGranted('ROLE_USER')]
class SavedPostController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SavedPostRepository $savedPostRepository,
        private PostRepository $postRepository
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $savedPosts = $this->savedPostRepository->findByUserUuid($user->getUuid());

        $data = array_map(fn(SavedPost $sp) => [
            'id' => $sp->getId(),
            'post' => [
                'id' => $sp->getPost()->getId(),
                'content' => $sp->getPost()->getContent(),
                'image_url' => $sp->getPost()->getImageUrl(),
                'author_uuid' => $sp->getPost()->getAuthorUuid(),
                'created_at' => $sp->getPost()->getCreatedAt()->format('c'),
                'likes_count' => $sp->getPost()->getLikesCount(),
            ],
            'saved_at' => $sp->getSavedAt()->format('c'),
        ], $savedPosts);

        return $this->json($data);
    }

    #[Route('/{postId}', methods: ['POST'])]
    public function save(int $postId): JsonResponse
    {
        $user = $this->getUser();
        $post = $this->postRepository->find($postId);

        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if already saved
        $existing = $this->savedPostRepository->findByUserAndPost($user->getUuid(), $postId);
        if ($existing) {
            return $this->json(['message' => 'Post already saved', 'saved' => true]);
        }

        $savedPost = new SavedPost();
        $savedPost->setUserUuid($user->getUuid());
        $savedPost->setPost($post);

        $this->em->persist($savedPost);
        $this->em->flush();

        return $this->json([
            'message' => 'Post saved successfully',
            'saved' => true,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{postId}', methods: ['DELETE'])]
    public function unsave(int $postId): JsonResponse
    {
        $user = $this->getUser();
        $savedPost = $this->savedPostRepository->findByUserAndPost($user->getUuid(), $postId);

        if (!$savedPost) {
            return $this->json(['error' => 'Saved post not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($savedPost);
        $this->em->flush();

        return $this->json([
            'message' => 'Post unsaved successfully',
            'saved' => false,
        ]);
    }

    #[Route('/check/{postId}', methods: ['GET'])]
    public function checkSaved(int $postId): JsonResponse
    {
        $user = $this->getUser();
        $isSaved = $this->savedPostRepository->isSavedByUser($user->getUuid(), $postId);

        return $this->json(['saved' => $isSaved]);
    }
}
