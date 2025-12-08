<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserDashboardController extends AbstractController
{
    private const ROLE_OPTIONS = [
        'ROLE_USER',
        'ROLE_ARTIST',
        'ROLE_ADMIN',
    ];

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'admin_users_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $role = $request->query->get('role');
        $search = trim((string)$request->query->get('q', ''));
        
        $qb = $this->userRepository->createQueryBuilder('u');
        
        // Exclude deleted users (soft delete)
        $qb->where('u.deletedAt IS NULL');
        
        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(u.email) LIKE :search OR LOWER(u.username) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }
        
        $users = $qb->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('user/admin_list.html.twig', [
            'users' => $users,
            'currentRole' => $role,
            'search' => $search,
            'roleOptions' => self::ROLE_OPTIONS,
        ]);
    }

    #[Route('/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_user', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_users_new');
            }

            $email = trim((string)$request->request->get('email'));
            $password = (string)$request->request->get('password');
            $roles = $request->request->all('roles') ?: [];

            if (!$email || !$password) {
                $this->addFlash('error', 'Email et mot de passe sont requis.');
                return $this->redirectToRoute('admin_users_new');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email invalide.');
                return $this->redirectToRoute('admin_users_new');
            }

            $user->setEmail($email);
            $user->setUsername($request->request->get('username') ?: null);
            $user->setIsActive($request->request->getBoolean('is_active', true));

            $normalizedRoles = array_values(array_intersect(self::ROLE_OPTIONS, $roles));
            if (empty($normalizedRoles)) {
                $normalizedRoles = ['ROLE_USER'];
            }
            $user->setRoles($normalizedRoles);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('user/admin_form.html.twig', [
            'user' => $user,
            'action' => 'new',
            'roleOptions' => self::ROLE_OPTIONS,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users_list');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_user_' . $id, (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $id]);
            }

            $email = trim((string)$request->request->get('email'));
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email invalide.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $id]);
            }

            $user->setEmail($email);
            $user->setUsername($request->request->get('username') ?: null);
            $user->setIsActive($request->request->getBoolean('is_active', true));

            $roles = $request->request->all('roles') ?: [];
            $normalizedRoles = array_values(array_intersect(self::ROLE_OPTIONS, $roles));
            if (empty($normalizedRoles)) {
                $normalizedRoles = ['ROLE_USER'];
            }
            $user->setRoles($normalizedRoles);

            $password = (string)$request->request->get('password');
            if ($password) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            }

            $this->em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');

            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('user/admin_form.html.twig', [
            'user' => $user,
            'action' => 'edit',
            'roleOptions' => self::ROLE_OPTIONS,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_users_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('admin_users_list');
        }

        if (!$this->isCsrfTokenValid('toggle_user_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_users_list');
        }

        $user->setIsActive(!$user->isActive());
        $this->em->flush();

        $this->addFlash('success', 'User status updated');

        return $this->redirectToRoute('admin_users_list');
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users_list');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users_list');
        }

        if (!$this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_users_list');
        }

        // Soft delete instead of hard delete
        $user->softDelete();
        $this->em->flush();

        $this->addFlash('success', 'Utilisateur déplacé vers la corbeille.');
        return $this->redirectToRoute('admin_users_list');
    }
}

