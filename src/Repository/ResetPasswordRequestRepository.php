<?php

namespace App\Repository;

use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * @deprecated ResetPasswordRequestRepository supprimé — stub conservé pour compatibilité.
 */
final class ResetPasswordRequestRepository implements ResetPasswordRequestRepositoryInterface
{
    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        throw new \LogicException('ResetPasswordRequestRepository removed: createResetPasswordRequest is not supported.');
    }

    public function getUserIdentifier(object $user): string
    {
        throw new \LogicException('ResetPasswordRequestRepository removed: getUserIdentifier is not supported.');
    }

    public function persistResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        // no-op stub
    }

    public function findResetPasswordRequest(string $selector): ?ResetPasswordRequestInterface
    {
        return null;
    }

    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        return null;
    }

    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        // no-op stub
    }

    public function removeExpiredResetPasswordRequests(): int
    {
        return 0;
    }
}
