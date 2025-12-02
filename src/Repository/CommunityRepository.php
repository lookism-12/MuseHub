<?php

namespace App\Repository;

use App\Entity\Community;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Community>
 */
class CommunityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Community::class);
    }

    public function findAllActive()
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category)
    {
        return $this->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('c.memberCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query)
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->orWhere('c.description LIKE :query')
            ->andWhere('c.isActive = :active')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('active', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
