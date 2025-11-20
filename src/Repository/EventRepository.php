<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateTime > :now')
            ->andWhere('e.isActive = :active')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('active', true)
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateTime >= :start')
            ->andWhere('e.dateTime <= :end')
            ->andWhere('e.isActive = :active')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('active', true)
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

