<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneByEmail($data['email']);
        
        // Don't reveal if user exists for security
        if (!$user) {
            return new JsonResponse(['message' => 'If the email exists, a reset link has been sent'], Response::HTTP_OK);
        }

        // Generate reset token (in production, store this in database with expiry)
        $token = bin2hex(random_bytes(32));
        
        // In production, save token to database
        // For now, we'll just send the email
        
        try {
            $resetUrl = "https://musehub.com/reset-password?token={$token}";
            
            $email = (new Email())
                ->from('noreply@musehub.com')
                ->to($user->getEmail())
                ->subject('Password Reset Request')
                ->html("Click here to reset your password: <a href='{$resetUrl}'>{$resetUrl}</a>");

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error
            error_log("Failed to send password reset email: " . $e->getMessage());
        }

        return new JsonResponse(['message' => 'If the email exists, a reset link has been sent'], Response::HTTP_OK);
    }

    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['token']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Token and password are required'], Response::HTTP_BAD_REQUEST);
        }

        // In production, validate token from database
        // For now, this is a simplified version
        
        $user = $this->userRepository->findOneByEmail($data['email'] ?? '');
        
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $this->em->flush();

        return new JsonResponse(['message' => 'Password reset successfully'], Response::HTTP_OK);
    }
}

