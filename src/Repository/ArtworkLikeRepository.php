<?php

namespace App\Repository;

use App\Entity\ArtworkLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtworkLike>
 *
 * @method ArtworkLike|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArtworkLike|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArtworkLike[]    findAll()
 * @method ArtworkLike[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtworkLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtworkLike::class);
    }

    public function findOneByUserAndArtwork(string $userUuid, int $artworkId): ?ArtworkLike
    {
        return $this->createQueryBuilder('al')
            ->join('al.user', 'u')
            ->join('al.artwork', 'a')
            ->where('u.uuid = :userUuid')
            ->andWhere('a.id = :artworkId')
            ->setParameter('userUuid', $userUuid)
            ->setParameter('artworkId', $artworkId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
