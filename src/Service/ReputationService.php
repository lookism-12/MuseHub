<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReputationService
{
    // Point values
    private const POINTS_POST_CREATED = 5;
    private const POINTS_COMMENT_CREATED = 2;
    private const POINTS_LIKE_RECEIVED = 2;
    private const POINTS_DISLIKE_RECEIVED = -1;
    private const POINTS_COMMENT_RECEIVED = 1;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Award points for creating a post
     */
    public function awardPostCreation(string $userUuid): void
    {
        $this->addPoints($userUuid, self::POINTS_POST_CREATED, 'post_created');
    }

    /**
     * Award points for creating a comment
     */
    public function awardCommentCreation(string $userUuid): void
    {
        $this->addPoints($userUuid, self::POINTS_COMMENT_CREATED, 'comment_created');
    }

    /**
     * Award points for receiving a like
     */
    public function awardLikeReceived(string $userUuid): void
    {
        $this->addPoints($userUuid, self::POINTS_LIKE_RECEIVED, 'like_received');
    }

    /**
     * Deduct points for receiving a dislike
     */
    public function awardDislikeReceived(string $userUuid): void
    {
        $this->addPoints($userUuid, self::POINTS_DISLIKE_RECEIVED, 'dislike_received');
    }

    /**
     * Award points for receiving a comment on your post
     */
    public function awardCommentReceived(string $userUuid): void
    {
        $this->addPoints($userUuid, self::POINTS_COMMENT_RECEIVED, 'comment_received');
    }

    /**
     * Add points to a user's reputation
     */
    private function addPoints(string $userUuid, int $points, string $reason): void
    {
        try {
            $user = $this->userRepository->findOneBy(['uuid' => $userUuid]);

            if (!$user) {
                $this->logger->warning('User not found for reputation update', [
                    'uuid' => $userUuid,
                    'reason' => $reason,
                ]);
                return;
            }

            $oldReputation = $user->getReputation();
            $user->addReputation($points);
            $this->em->flush();

            $this->logger->info('Reputation updated', [
                'uuid' => $userUuid,
                'reason' => $reason,
                'points' => $points,
                'old_reputation' => $oldReputation,
                'new_reputation' => $user->getReputation(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update reputation', [
                'uuid' => $userUuid,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get reputation level name based on points
     */
    public function getReputationLevel(int $reputation): string
    {
        return match (true) {
            $reputation >= 1000 => 'Legend',
            $reputation >= 500 => 'Expert',
            $reputation >= 250 => 'Advanced',
            $reputation >= 100 => 'Intermediate',
            $reputation >= 50 => 'Contributor',
            $reputation >= 10 => 'Newcomer',
            default => 'Beginner',
        };
    }

    /**
     * Get reputation badge color
     */
    public function getReputationBadgeColor(int $reputation): string
    {
        return match (true) {
            $reputation >= 1000 => 'linear-gradient(135deg, #7f00ff, #ff9cb6)', // Platinum (brand colors)
            $reputation >= 500 => 'linear-gradient(135deg, #FFD700, #FFA500)', // Gold
            $reputation >= 250 => 'linear-gradient(135deg, #C0C0C0, #E8E8E8)', // Silver
            $reputation >= 100 => 'linear-gradient(135deg, #CD7F32, #E8A87C)', // Bronze
            default => 'linear-gradient(135deg, #6B7280, #9CA3AF)', // Gray
        };
    }
}
