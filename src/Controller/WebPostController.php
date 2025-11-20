<?php
namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebPostController extends AbstractController
{
    #[Route('/posts', name: 'post_index', methods: ['GET', 'POST'])]
    #[Route('/community/posts', name: 'community_posts', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, PostRepository $postRepo): Response
    {
        if ($request->isMethod('POST')) {
            $authorUuid = $request->request->get('author_uuid');
            $content = $request->request->get('content');
            $imageUrl = $request->request->get('image_url');

            if ($authorUuid && $content) {
                $post = new Post();
                $post->setAuthorUuid($authorUuid);
                $post->setContent($content);
                $post->setImageUrl($imageUrl);

                $em->persist($post);
                $em->flush();

                return $this->redirectToRoute('post_index');
            }
        }

        $posts = $postRepo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('post/index.html.twig', ['posts' => $posts]);
    }

    #[Route('/posts/{id}', name: 'post_show', methods: ['GET', 'POST'])]
    #[Route('/community/posts/{id}', name: 'community_post_show', methods: ['GET', 'POST'])]
    public function show(int $id, Request $request, EntityManagerInterface $em, PostRepository $postRepo): Response
    {
        $post = $postRepo->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Post non trouvé');
        }

        if ($request->isMethod('POST')) {
            $commenterUuid = $request->request->get('commenter_uuid');
            $content = $request->request->get('content');

            if ($commenterUuid && $content) {
                $comment = new Comment();
                $comment->setPost($post);
                $comment->setCommenterUuid($commenterUuid);
                $comment->setContent($content);

                $em->persist($comment);
                $em->flush();

                return $this->redirectToRoute('post_show', ['id' => $id]);
            }
        }

        return $this->render('post/show.html.twig', ['post' => $post]);
    }
}
