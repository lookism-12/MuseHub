<?php

namespace App\Repository;

use App\Entity\EventNotification;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventNotification::class);
    }

    /**
     * Récupère les notifications en attente qui doivent être envoyées
     */
    public function findPendingNotifications(\DateTimeInterface $before = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'pending');

        if ($before) {
            $qb->andWhere('n.scheduledAt <= :before')
               ->setParameter('before', $before);
        }

        return $qb->orderBy('n.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications pour un événement spécifique
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.event = :event')
            ->setParameter('event', $event)
            ->orderBy('n.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications pour un utilisateur
     */
    public function findByUser(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('n.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('n.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une notification pending existe déjà (pour éviter les doublons)
     */
    public function notificationExists(Event $event, User $user, string $type): bool
    {
        $count = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.event = :event')
            ->andWhere('n.user = :user')
            ->andWhere('n.type = :type')
            ->andWhere('n.status = :status')
            ->setParameter('event', $event)
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Supprime les notifications pour un événement
     */
    public function deleteByEvent(Event $event): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les statistiques des notifications
     */
    public function getStatistics(): array
    {
        $result = $this->createQueryBuilder('n')
            ->select('n.status, COUNT(n.id) as count')
            ->groupBy('n.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($result as $row) {
            $stats[$row['status']] = (int)$row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les notifications échouées avec retry < 3
     */
    public function findFailedForRetry(int $maxRetries = 3): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.retryCount < :maxRetries')
            ->setParameter('status', 'failed')
            ->setParameter('maxRetries', $maxRetries)
            ->orderBy('n.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
