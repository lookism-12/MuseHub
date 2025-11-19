<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $formData = [
            'email' => $request->request->get('email', ''),
            'username' => $request->request->get('username', ''),
            'accept_terms' => (bool)$request->request->get('accept_terms', false),
        ];

        if ($request->isMethod('POST')) {
            $email = trim((string)$request->request->get('email'));
            $username = trim((string)$request->request->get('username'));
            $password = (string)$request->request->get('password');
            $confirmPassword = (string)$request->request->get('password_confirm');
            $acceptTerms = $request->request->getBoolean('accept_terms');

            $formData = [
                'email' => $email,
                'username' => $username,
                'accept_terms' => $acceptTerms,
            ];

            if (!$this->isCsrfTokenValid('register_form', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Le formulaire est expiré. Merci de réessayer.');
            } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Merci de fournir une adresse email valide.');
            }

            elseif ($userRepository->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
            } elseif (!$password || strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            } elseif ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif (!$acceptTerms) {
                $this->addFlash('error', 'Vous devez accepter les conditions d’utilisation.');
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username ?: null);
                $user->setRoles(['ROLE_USER']);
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $formData,
        ]);
    }

    #[Route('/api/auth/register', name: 'api_auth_register_form', methods: ['GET'])]
    public function apiRegisterRedirect(): Response
    {
        return $this->redirectToRoute('register');
    }
}


