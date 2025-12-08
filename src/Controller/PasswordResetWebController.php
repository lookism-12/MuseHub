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

    /**
     * -------------------------------------------------------------------------
     * PAGE : Mot de passe oublié
     * -------------------------------------------------------------------------
     * Corrections apportées :
     * 1️⃣ Envoi email → adresse dynamique ($user->getEmail())
     * 2️⃣ Utilisation d’un paramètre mailer_from (propre et configurable)
     * 3️⃣ Ajout de logs et affichage du lien en DEV si le mail échoue
     * 4️⃣ Vérification token vide + gestion propre des erreurs
     */
    #[Route('/forgot-password', name: 'password_forgot', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $email = '';

        if ($request->isMethod('POST')) {

            // Vérification CSRF
            if (!$this->isCsrfTokenValid('forgot_password_form', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Le formulaire est expiré, merci de réessayer.');
                return $this->redirectToRoute('password_forgot');
            }

            // Email soumis
            $email = trim((string)$request->request->get('email'));

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $user = $this->userRepository->findOneBy(['email' => $email]);

                if ($user) {

                    // Création du token
                    $token = $this->passwordResetManager->createToken($user);

                    if (!$token) {
                        error_log('[PasswordReset] createToken returned EMPTY for ' . $email);
                        $this->addFlash('warning', 'Erreur interne. Impossible de générer le lien.');
                        return $this->redirectToRoute('password_forgot');
                    }

                    // Création du lien
                    $resetUrl = $this->urlGenerator->generate(
                        'password_reset_form',
                        ['token' => $token],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    try {
                        // Récupération propre de l’adresse FROM
                        $fromEmail = $this->getParameter('mailer_from') ?? 'amenimakdouli@gmail.com';

                        $message = (new Email())
                            ->from($fromEmail)
                            ->to($user->getEmail())    // 🔥 Correction : on envoie au bon user
                            ->subject('Réinitialisation du mot de passe MuseHub')
                            ->html(sprintf(
                                'Cliquez ici pour réinitialiser votre mot de passe : <a href="%s">%s</a>',
                                $resetUrl,
                                $resetUrl
                            ));

                        // ENVOI DU MAIL
                        $this->mailer->send($message);

                        $this->addFlash('success', 'Un email de réinitialisation a été envoyé à votre adresse.');

                    } catch (\Throwable $e) {

                        // Log utile pour debug
                        error_log('[PasswordReset] Email error: ' . $e->getMessage());

                        $this->addFlash(
                            'warning',
                            'Impossible d\'envoyer l\'email. Réessayez plus tard.'
                        );

                        // En DEV → on affiche quand même le lien pour tester !
                        if ($this->getParameter('kernel.environment') !== 'prod') {
                            $this->addFlash('info', 'Lien (mode DEV) : ' . $resetUrl);
                            error_log('[PasswordReset] DEV resetUrl: ' . $resetUrl);
                        }
                    }

                } else {
                    $this->addFlash('error', 'Aucun compte n’est associé à cet email.');
                }

            } else {
                $this->addFlash('error', 'Veuillez entrer une adresse email valide.');
            }

            return $this->redirectToRoute('password_forgot');
        }

        return $this->render('security/password_forgot.html.twig', [
            'email' => $email,
        ]);
    }

    /**
     * -------------------------------------------------------------------------
     * PAGE : Réinitialisation du mot de passe
     * -------------------------------------------------------------------------
     */
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
                $this->addFlash('error', 'Le formulaire est expiré.');
                return $this->redirectToRoute('password_reset_form', ['token' => $token]);
            }

            $password = (string)$request->request->get('password');
            $confirm = (string)$request->request->get('password_confirm');

            if (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            } elseif ($password !== $confirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } else {

                // Mise à jour
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $password)
                );

                // On supprime le token
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
