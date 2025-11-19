<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class PasswordResetManager
{
    private const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {
    }

    public function createToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetToken($token);
        $user->setPasswordResetRequestedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $token;
    }

    public function findUserForToken(string $token): ?User
    {
        if (!$token) {
            return null;
        }

        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);
        if (!$user) {
            return null;
        }

        $requestedAt = $user->getPasswordResetRequestedAt();
        if (!$requestedAt) {
            return null;
        }

        $expiresAt = (clone $requestedAt)->modify('+' . self::TOKEN_TTL_MINUTES . ' minutes');
        if ($expiresAt < new \DateTimeImmutable()) {
            return null;
        }

        return $user;
    }

    public function clearToken(User $user): void
    {
        $user->setPasswordResetToken(null);
        $user->setPasswordResetRequestedAt(null);
        $this->em->flush();
    }
}


