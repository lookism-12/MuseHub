<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    public function findByEventUuid(string $eventUuid): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.eventUuid = :eventUuid')
            ->andWhere('p.status = :status')
            ->setParameter('eventUuid', $eventUuid)
            ->setParameter('status', 'confirmed')
            ->getQuery()
            ->getResult();
    }

    public function findExisting(string $eventUuid, string $participantUuid): ?Participant
    {
        return $this->createQueryBuilder('p')
            ->where('p.eventUuid = :eventUuid')
            ->andWhere('p.participantUuid = :participantUuid')
            ->setParameter('eventUuid', $eventUuid)
            ->setParameter('participantUuid', $participantUuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByParticipantUuid(string $participantUuid): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.participantUuid = :participantUuid')
            ->setParameter('participantUuid', $participantUuid)
            ->getQuery()
            ->getResult();
    }
}

