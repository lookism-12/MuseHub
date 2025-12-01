<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Find unread notifications for a user
     * @return Notification[]
     */
    public function findUnreadByUser(string $userUuid, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipientUuid = :uuid')
            ->andWhere('n.isRead = false')
            ->setParameter('uuid', $userUuid)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all notifications for a user with pagination
     * @return Notification[]
     */
    public function findByUser(string $userUuid, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipientUuid = :uuid')
            ->setParameter('uuid', $userUuid)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnreadByUser(string $userUuid): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipientUuid = :uuid')
            ->andWhere('n.isRead = false')
            ->setParameter('uuid', $userUuid)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsReadByUser(string $userUuid): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.recipientUuid = :uuid')
            ->andWhere('n.isRead = false')
            ->setParameter('uuid', $userUuid)
            ->getQuery()
            ->execute();
    }

    /**
     * Check if a similar notification already exists (for deduplication)
     */
    public function findSimilarRecent(
        string $recipientUuid,
        string $type,
        ?int $postId,
        ?int $commentId,
        int $withinMinutes = 5
    ): ?Notification {
        $qb = $this->createQueryBuilder('n')
            ->where('n.recipientUuid = :recipientUuid')
            ->andWhere('n.type = :type')
            ->andWhere('n.createdAt > :since')
            ->setParameter('recipientUuid', $recipientUuid)
            ->setParameter('type', $type)
            ->setParameter('since', new \DateTimeImmutable("-{$withinMinutes} minutes"))
            ->setMaxResults(1);

        if ($postId !== null) {
            $qb->andWhere('n.postId = :postId')
                ->setParameter('postId', $postId);
        }

        if ($commentId !== null) {
            $qb->andWhere('n.commentId = :commentId')
                ->setParameter('commentId', $commentId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
