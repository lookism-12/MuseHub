<?php
namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/posts')]
class PostController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepository
    ) {}

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
        ], $posts);

        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['author_uuid']) || empty($data['content'])) {
            return $this->json(['error' => 'author_uuid and content are required'], 400);
        }

        $post = new Post();
        $post->setAuthorUuid($data['author_uuid']);
        $post->setContent($data['content']);
        $post->setImageUrl($data['image_url'] ?? null);

        $this->em->persist($post);
        $this->em->flush();

        return $this->json(['id' => $post->getId()], 201);
    }

    #[Route('/{id}/comments', methods: ['POST'])]
    public function comment(int $id, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['commenter_uuid']) || empty($data['content'])) {
            return $this->json(['error' => 'commenter_uuid and content are required'], 400);
        }

        $comment = new \App\Entity\Comment();
        $comment->setPost($post);
        $comment->setCommenterUuid($data['commenter_uuid']);
        $comment->setContent($data['content']);

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json(['id' => $comment->getId()], 201);
    }
}
