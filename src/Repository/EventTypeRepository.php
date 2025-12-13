<?php

namespace App\Repository;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventType>
 */
class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('et')
            ->where('et.is_active = :active')
            ->setParameter('active', true)
            ->orderBy('et.sort_order', 'ASC')
            ->addOrderBy('et.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCapacityType(string $capacityType): array
    {
        return $this->createQueryBuilder('et')
            ->where('et.capacity_type = :type')
            ->andWhere('et.is_active = :active')
            ->setParameter('type', $capacityType)
            ->setParameter('active', true)
            ->orderBy('et.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPaidEventTypes(): array
    {
        return $this->createQueryBuilder('et')
            ->where('et.requires_payment = :paid')
            ->andWhere('et.is_active = :active')
            ->setParameter('paid', true)
            ->setParameter('active', true)
            ->orderBy('et.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
