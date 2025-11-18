<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserDashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'admin_users_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $role = $request->query->get('role');
        
        $qb = $this->userRepository->createQueryBuilder('u');
        
        if ($role) {
            $qb->where('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }
        
        $users = $qb->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('user/admin_list.html.twig', [
            'users' => $users,
            'currentRole' => $role,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_users_toggle', methods: ['POST'])]
    public function toggle(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('admin_users_list');
        }

        $user->setIsActive(!$user->isActive());
        $this->em->flush();

        $this->addFlash('success', 'User status updated');

        return $this->redirectToRoute('admin_users_list');
    }
}

