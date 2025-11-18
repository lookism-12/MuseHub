<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/community')]
#[IsGranted('ROLE_ADMIN')]
class CommunityDashboardController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository,
        private CommentRepository $commentRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'admin_community_list', methods: ['GET'])]
    public function list(): Response
    {
        $posts = $this->postRepository->findBy([], ['createdAt' => 'DESC'], 100);
        
        // Calculate daily posts
        $dailyPosts = [];
        foreach ($posts as $post) {
            $date = $post->getCreatedAt()->format('Y-m-d');
            $dailyPosts[$date] = ($dailyPosts[$date] ?? 0) + 1;
        }

        $stats = [
            'total_posts' => $this->postRepository->count([]),
            'total_comments' => $this->commentRepository->count([]),
            'daily_posts' => $dailyPosts,
        ];

        return $this->render('community/admin_list.html.twig', [
            'posts' => $posts,
            'stats' => $stats,
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'admin_community_post_delete', methods: ['POST'])]
    public function deletePost(int $id): Response
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post not found');
            return $this->redirectToRoute('admin_community_list');
        }

        $this->em->remove($post);
        $this->em->flush();

        $this->addFlash('success', 'Post deleted');

        return $this->redirectToRoute('admin_community_list');
    }
}

