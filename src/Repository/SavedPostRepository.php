<?php

namespace App\Repository;

use App\Entity\SavedPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SavedPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedPost::class);
    }

    /**
     * Find all saved posts for a user
     */
    public function findByUserUuid(string $userUuid): array
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.userUuid = :userUuid')
            ->setParameter('userUuid', $userUuid)
            ->orderBy('sp.savedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a post is saved by a user
     */
    public function isSavedByUser(string $userUuid, int $postId): bool
    {
        $count = $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->where('sp.userUuid = :userUuid')
            ->andWhere('sp.post = :postId')
            ->setParameter('userUuid', $userUuid)
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Find a saved post by user and post
     */
    public function findByUserAndPost(string $userUuid, int $postId): ?SavedPost
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.userUuid = :userUuid')
            ->andWhere('sp.post = :postId')
            ->setParameter('userUuid', $userUuid)
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
