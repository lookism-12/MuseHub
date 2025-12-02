<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.date >= :start')
            ->andWhere('t.date <= :end')
            ->andWhere('t.status = :status')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'paid')
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenue(\DateTimeImmutable $start = null, \DateTimeImmutable $end = null): string
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as total')
            ->where('t.status = :status')
            ->setParameter('status', 'paid');

        if ($start) {
            $qb->andWhere('t.date >= :start')
                ->setParameter('start', $start);
        }

        if ($end) {
            $qb->andWhere('t.date <= :end')
                ->setParameter('end', $end);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ?? '0.00';
    }
}

