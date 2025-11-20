<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostAdminController extends AbstractController
{
    #[Route('/admin/posts', name: 'admin_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findWithComments();

        return $this->render('post/admin.html.twig', [
            'posts' => $posts,
        ]);
    }
}
