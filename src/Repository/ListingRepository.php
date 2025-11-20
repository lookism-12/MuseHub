<?php

namespace App\Repository;

use App\Entity\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.stock > 0')
            ->setParameter('status', 'available')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

