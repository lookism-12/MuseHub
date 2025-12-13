<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * Find pending reports
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count reports for specific content
     */
    public function countReportsForContent(string $contentType, int $contentId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.contentType = :type')
            ->andWhere('r.contentId = :id')
            ->andWhere('r.status = :status')
            ->setParameter('type', $contentType)
            ->setParameter('id', $contentId)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if user already reported this content
     */
    public function hasUserReported(string $userUuid, string $contentType, int $contentId): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reporterUuid = :uuid')
            ->andWhere('r.contentType = :type')
            ->andWhere('r.contentId = :id')
            ->setParameter('uuid', $userUuid)
            ->setParameter('type', $contentType)
            ->setParameter('id', $contentId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
