<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/trash')]
class TrashController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}

    #[Route('', name: 'admin_trash_index')]
    public function index(): Response
    {
        return $this->render('admin/trash/index.html.twig', [
            'users' => $this->userRepository->findDeleted(),
        ]);
    }

    #[Route('/{id}/restore', name: 'admin_trash_restore', methods: ['POST'])]
    public function restore(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('restore'.$user->getId(), $request->request->get('_token'))) {
            $this->userRepository->restore($user);
            $this->addFlash('success', 'Utilisateur restauré avec succès.');
        }

        return $this->redirectToRoute('admin_trash_index');
    }

    #[Route('/{id}/delete-permanently', name: 'admin_trash_delete_permanently', methods: ['POST'])]
    public function deletePermanently(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete_permanently'.$user->getId(), $request->request->get('_token'))) {
            $this->userRepository->hardDelete($user);
            $this->addFlash('success', 'Utilisateur supprimé définitivement.');
        }

        return $this->redirectToRoute('admin_trash_index');
    }
}
