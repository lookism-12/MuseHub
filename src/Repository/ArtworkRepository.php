<?php

namespace App\Repository;

use App\Entity\Artwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artwork>
 */
class ArtworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artwork::class);
    }

    public function search(?int $categoryId, ?string $artistUuid, ?float $minPrice, ?float $maxPrice): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($categoryId !== null) {
            $qb->andWhere('a.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($artistUuid !== null) {
            $qb->andWhere('a.artistUuid = :artistUuid')
               ->setParameter('artistUuid', $artistUuid);
        }

        if ($minPrice !== null) {
            $qb->andWhere('a.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('a.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        return $qb
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
