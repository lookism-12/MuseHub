<?php

namespace App\EventSubscriber;

use App\Service\NotificationManager;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use App\Entity\Participant;

#[AsDoctrineListener(event: Events::postPersist, priority: 500)]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500)]
class ParticipantSubscriber
{
    public function __construct(
        private NotificationManager $notificationManager,
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Quand un participant s'inscrit, planifier les notifications
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Participant) {
            return;
        }

        $eventUuid = $entity->getEventUuid();
        $participantUuid = $entity->getParticipantUuid();

        $this->logger->info("ParticipantSubscriber triggered", [
            'eventUuid' => $eventUuid,
            'participantUuid' => $participantUuid
        ]);

        $event = $this->eventRepository->findOneBy(['uuid' => $eventUuid]);
        $user = $this->userRepository->findOneBy(['uuid' => $participantUuid]);

        if (!$event || !$user) {
            $this->logger->warning("Event or User not found", [
                'eventFound' => $event !== null,
                'userFound' => $user !== null
            ]);
            return;
        }

        $this->logger->info("Creating notifications", [
            'event' => $event->getTitle(),
            'user' => $user->getEmail(),
            'eventDate' => $event->getDateTime()->format('Y-m-d H:i:s')
        ]);

        $now = new \DateTimeImmutable();
        
        // Notification immédiate de confirmation
        $confirmNotif = $this->notificationManager->scheduleNotification(
            $event,
            $user,
            'event_created',
            $now,
            'email'
        );
        $this->logger->info("Confirmation notification", ['created' => $confirmNotif !== null]);

        // Rappel 24h avant
        $reminder24h = $event->getDateTime()->modify('-24 hours');
        $this->logger->info("Reminder 24h", [
            'scheduledAt' => $reminder24h->format('Y-m-d H:i:s'),
            'isFuture' => $reminder24h > $now
        ]);
        
        if ($reminder24h > $now) {
            $notif24h = $this->notificationManager->scheduleNotification(
                $event,
                $user,
                'reminder_24h',
                $reminder24h
            );
            $this->logger->info("24h reminder created", ['created' => $notif24h !== null]);
        }

        // Rappel 1h avant
        $reminder1h = $event->getDateTime()->modify('-1 hour');
        $this->logger->info("Reminder 1h", [
            'scheduledAt' => $reminder1h->format('Y-m-d H:i:s'),
            'isFuture' => $reminder1h > $now
        ]);
        
        if ($reminder1h > $now) {
            $notif1h = $this->notificationManager->scheduleNotification(
                $event,
                $user,
                'reminder_1h',
                $reminder1h
            );
            $this->logger->info("1h reminder created", ['created' => $notif1h !== null]);
        }

        // Sauvegarder les notifications
        $this->em->flush();
        $this->logger->info("Notifications flushed to database");
    }

    /**
     * Quand un événement est mis à jour, notifier les participants
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof \App\Entity\Event) {
            return;
        }

        // Vérifier si des champs importants ont changé
        $changeSet = $args->getObjectManager()
            ->getUnitOfWork()
            ->getEntityChangeSet($entity);

        $importantFields = ['title', 'dateTime', 'location', 'description'];
        $hasImportantChanges = false;

        foreach ($importantFields as $field) {
            if (isset($changeSet[$field])) {
                $hasImportantChanges = true;
                break;
            }
        }

        if ($hasImportantChanges) {
            $this->notificationManager->notifyParticipants(
                $entity,
                'event_updated'
            );
        }
    }
}
