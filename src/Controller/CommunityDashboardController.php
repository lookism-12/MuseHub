<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Service\ContentFilter;
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
        private EntityManagerInterface $em,
        private ContentFilter $contentFilter
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

    #[Route('/posts/new', name: 'admin_community_post_new', methods: ['GET', 'POST'])]
    public function newPost(Request $request): Response
    {
        $post = new Post();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_admin_post', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_community_post_new');
            }

            $content = trim((string)$request->request->get('content'));
            if (!$content) {
                $this->addFlash('error', 'Le contenu est requis.');
                return $this->redirectToRoute('admin_community_post_new');
            }

            $filter = $this->contentFilter->filterContent($content);
            if (!$filter['isValid']) {
                $this->addFlash('error', implode(', ', $filter['issues']));
                return $this->redirectToRoute('admin_community_post_new');
            }

            $authorUuid = $request->request->get('author_uuid') ?: ($this->getUser()?->getUuid());
            if (!$authorUuid) {
                $this->addFlash('error', 'UUID auteur requis.');
                return $this->redirectToRoute('admin_community_post_new');
            }

            $post->setAuthorUuid($authorUuid);
            $post->setContent($filter['filteredContent']);
            $post->setImageUrl($request->request->get('image_url') ?: null);

            $this->em->persist($post);
            $this->em->flush();

            $this->addFlash('success', 'Post publié.');
            return $this->redirectToRoute('admin_community_list');
        }

        return $this->render('community/admin_form.html.twig', [
            'post' => $post,
            'action' => 'new',
        ]);
    }

    #[Route('/posts/{id}/edit', name: 'admin_community_post_edit', methods: ['GET', 'POST'])]
    public function editPost(int $id, Request $request): Response
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('admin_community_list');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_admin_post_' . $id, (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_community_post_edit', ['id' => $id]);
            }

            $content = trim((string)$request->request->get('content'));
            if (!$content) {
                $this->addFlash('error', 'Le contenu est requis.');
                return $this->redirectToRoute('admin_community_post_edit', ['id' => $id]);
            }

            $filter = $this->contentFilter->filterContent($content);
            if (!$filter['isValid']) {
                $this->addFlash('error', implode(', ', $filter['issues']));
                return $this->redirectToRoute('admin_community_post_edit', ['id' => $id]);
            }

            $post->setContent($filter['filteredContent']);
            $post->setImageUrl($request->request->get('image_url') ?: null);

            $authorUuid = $request->request->get('author_uuid');
            if ($authorUuid) {
                $post->setAuthorUuid($authorUuid);
            }

            $this->em->flush();
            $this->addFlash('success', 'Post mis à jour.');

            return $this->redirectToRoute('admin_community_list');
        }

        return $this->render('community/admin_form.html.twig', [
            'post' => $post,
            'action' => 'edit',
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'admin_community_post_delete', methods: ['POST'])]
    public function deletePost(int $id, Request $request): Response
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post not found');
            return $this->redirectToRoute('admin_community_list');
        }

        if (!$this->isCsrfTokenValid('delete_post_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_community_list');
        }

        $this->em->remove($post);
        $this->em->flush();

        $this->addFlash('success', 'Post deleted');

        return $this->redirectToRoute('admin_community_list');
    }
}

