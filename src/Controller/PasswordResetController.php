<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\PasswordResetManager;
use Psr\Log\LoggerInterface;

#[Route('/api/auth')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private PasswordResetManager $passwordResetManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {}



    /* ============================================================
            1️⃣  REQUEST PASSWORD RESET (send email)
       ============================================================ */
    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifier que l'email est fourni
        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l’utilisateur existe
        $user = $this->userRepository->findOneByEmail($data['email']);
        if (!$user) {
            $this->logger->warning('Password reset requested for non-existent email', ['email' => $data['email']]);

            return new JsonResponse([
                'error' => 'Aucun compte créé avec cet email',
            ], Response::HTTP_NOT_FOUND);
        }

        // Générer un token
        $token = $this->passwordResetManager->createToken($user);



        /* ---------------------------- ENVOI EMAIL ---------------------------- */
        try {
            // Lien de réinitialisation
            $resetUrl = $this->urlGenerator->generate('password_reset_form', [
                'token' => $token,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // Debug avant envoi
            $this->logger->info('Preparing email sending', [
                'email' => $user->getEmail(),
                'reset_url' => $resetUrl
            ]);

            // EMAIL → envoyé au user
            $fromEmail = $this->getParameter('mailer_from') ?? 'no-reply@musehub.com'; // Fallback générique
            
            $email = (new Email())
                ->from($fromEmail)
                ->to($user->getEmail())               // adresse réelle du user
                ->subject('Password Reset Request')
                ->html("
                    <p>Bonjour,</p>
                    <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
                    <p><a href='{$resetUrl}'>Réinitialiser le mot de passe</a></p>
                    <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
                ");

            // Envoi réel
            $this->mailer->send($email);

            $this->logger->info('Password reset email sent successfully', [
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {

            // Log complet de l'erreur
            $this->logger->error('Failed to send password reset email', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Afficher le lien en DEV pour tester
            if ($this->getParameter('kernel.environment') === 'dev') {
                return new JsonResponse([
                    'message' => 'Email sending failed (DEV MODE)',
                    'error' => $e->getMessage(),
                    'reset_url' => $resetUrl ?? null,
                    'token' => $token
                ], Response::HTTP_OK);
            }
        }

        // Return link in DEV mode for easier testing
        $response = [
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
        ];

        if ($this->getParameter('kernel.environment') === 'dev') {
            $response['dev_mode_info'] = [
                'note' => 'You are in DEV mode. Here is the link:',
                'reset_url' => $resetUrl,
                'token' => $token
            ];
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }




    /* ============================================================
            2️⃣  RESET PASSWORD WITH TOKEN
       ============================================================ */
    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifier le token & password
        if (!isset($data['token']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Token and password are required'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si token existe et user associé
        $user = $this->passwordResetManager->findUserForToken($data['token']);
        if (!$user) {
            $this->logger->warning('Invalid or expired token used', [
                'token' => substr($data['token'], 0, 10) . '...'
            ]);

            return new JsonResponse(['error' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }

        // Vérification longueur mot de passe
        if (strlen((string)$data['password']) < 6) {
            return new JsonResponse(['error' => 'Password must be at least 6 characters'], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour du mot de passe
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        // Supprimer le token après utilisation
        $this->passwordResetManager->clearToken($user);

        $this->logger->info('Password reset successfully', ['email' => $user->getEmail()]);

        return new JsonResponse(['message' => 'Password reset successfully'], Response::HTTP_OK);
    }
}
