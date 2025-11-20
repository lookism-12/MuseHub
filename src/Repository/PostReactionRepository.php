<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\PostReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostReaction>
 *
 * @method PostReaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostReaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostReaction[]    findAll()
 * @method PostReaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostReaction::class);
    }

    /**
     * @param array<int> $postIds
     * @return array<int, PostReaction>
     */
    public function findByUserAndPostIds(string $userUuid, array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->andWhere('r.userUuid = :uuid')
            ->andWhere('r.post IN (:postIds)')
            ->setParameter('uuid', $userUuid)
            ->setParameter('postIds', $postIds)
            ->getQuery()
            ->getResult();
    }

    public function findOneByPostAndUser(Post $post, string $userUuid): ?PostReaction
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.post = :post')
            ->andWhere('r.userUuid = :uuid')
            ->setParameter('post', $post)
            ->setParameter('uuid', $userUuid)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

