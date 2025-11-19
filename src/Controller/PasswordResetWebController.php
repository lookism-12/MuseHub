<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\PasswordResetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetWebController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordResetManager $passwordResetManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    #[Route('/forgot-password', name: 'password_forgot', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $email = '';

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password_form', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Le formulaire est expiré, merci de réessayer.');
                return $this->redirectToRoute('password_forgot');
            }

            $email = trim((string)$request->request->get('email'));
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = $this->userRepository->findOneBy(['email' => $email]);
                if ($user) {
                    $token = $this->passwordResetManager->createToken($user);
                    $resetUrl = $this->urlGenerator->generate('password_reset_form', [
                        'token' => $token,
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    try {
                        $message = (new Email())
                            ->from('noreply@musehub.com')
                            ->to($user->getEmail())
                            ->subject('Réinitialisation du mot de passe MuseHub')
                            ->html(sprintf(
                                'Cliquez sur ce lien pour réinitialiser votre mot de passe : <a href="%s">%s</a>',
                                $resetUrl,
                                $resetUrl
                            ));
                        $this->mailer->send($message);
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'Impossible d\'envoyer l\'email. Réessayez plus tard.');
                        return $this->redirectToRoute('password_forgot');
                    }
                }
            }

            $this->addFlash('success', 'Si un compte existe, un email a été envoyé.');
            return $this->redirectToRoute('password_forgot');
        }

        return $this->render('security/password_forgot.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'password_reset_form', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = $this->passwordResetManager->findUserForToken($token);
        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('password_forgot');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password_form', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Le formulaire est expiré, merci de réessayer.');
                return $this->redirectToRoute('password_reset_form', ['token' => $token]);
            }

            $password = (string)$request->request->get('password');
            $confirm = (string)$request->request->get('password_confirm');

            if (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            } elseif ($password !== $confirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } else {
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                $this->passwordResetManager->clearToken($user);
                $this->addFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('security/password_reset.html.twig', [
            'token' => $token,
        ]);
    }
}


