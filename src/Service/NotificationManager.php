<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventNotification;
use App\Entity\User;
use App\Repository\EventNotificationRepository;
use App\Repository\ParticipantRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class NotificationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventNotificationRepository $notificationRepository,
        private ParticipantRepository $participantRepository,
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    /**
     * Planifie les notifications pour un événement
     */
    public function scheduleNotificationsForEvent(Event $event): void
    {
        $participants = $this->participantRepository->findBy(['eventUuid' => $event->getUuid()]);
        
        foreach ($participants as $participant) {
            $user = $this->userRepository->findOneBy(['uuid' => $participant->getParticipantUuid()]);
            if (!$user) {
                continue;
            }

            // Rappel 24h avant
            $this->scheduleNotification(
                $event,
                $user,
                'reminder_24h',
                $event->getDateTime()->modify('-24 hours')
            );

            // Rappel 1h avant
            $this->scheduleNotification(
                $event,
                $user,
                'reminder_1h',
                $event->getDateTime()->modify('-1 hour')
            );
        }

        $this->em->flush();
    }

    /**
     * Crée une notification planifiée
     */
    public function scheduleNotification(
        Event $event,
        User $user,
        string $type,
        \DateTimeInterface $scheduledAt,
        string $channel = 'email'
    ): ?EventNotification {
        // Vérifier si la notification existe déjà
        if ($this->notificationRepository->notificationExists($event, $user, $type)) {
            return null;
        }

        // Ne pas planifier si la date est déjà passée
        if ($scheduledAt < new \DateTimeImmutable()) {
            return null;
        }

        $notification = new EventNotification();
        $notification->setEvent($event);
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setScheduledAt($scheduledAt);
        $notification->setChannel($channel);
        $notification->setMessage($this->generateMessage($event, $type));

        $this->em->persist($notification);

        return $notification;
    }

    /**
     * Envoie une notification immédiate à tous les participants
     */
    public function notifyParticipants(Event $event, string $type, string $customMessage = null): int
    {
        $participants = $this->participantRepository->findBy(['eventUuid' => $event->getUuid()]);
        $sentCount = 0;

        foreach ($participants as $participant) {
            $user = $this->userRepository->findOneBy(['uuid' => $participant->getParticipantUuid()]);
            if (!$user) {
                continue;
            }

            $notification = new EventNotification();
            $notification->setEvent($event);
            $notification->setUser($user);
            $notification->setType($type);
            $notification->setScheduledAt(new \DateTimeImmutable());
            $notification->setMessage($customMessage ?? $this->generateMessage($event, $type));

            $this->em->persist($notification);
            
            // Envoyer immédiatement
            if ($this->sendNotification($notification)) {
                $sentCount++;
            }
        }

        $this->em->flush();
        
        return $sentCount;
    }

    /**
     * Envoie les notifications en attente
     */
    public function sendPendingNotifications(): int
    {
        $notifications = $this->notificationRepository->findPendingNotifications(new \DateTimeImmutable());
        $sentCount = 0;

        foreach ($notifications as $notification) {
            if ($this->sendNotification($notification)) {
                $sentCount++;
            }
        }

        $this->em->flush();
        
        return $sentCount;
    }

    /**
     * Envoie une notification
     */
    public function sendNotification(EventNotification $notification): bool
    {
        try {
            switch ($notification->getChannel()) {
                case 'email':
                    $this->sendEmailNotification($notification);
                    break;
                case 'sms':
                    // TODO: Implémenter l'envoi SMS
                    $this->logger->info('SMS notification not implemented yet');
                    break;
                case 'push':
                    // TODO: Implémenter push notification
                    $this->logger->info('Push notification not implemented yet');
                    break;
            }

            $notification->markAsSent();
            $this->logger->info('Notification sent', [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'user' => $notification->getUser()->getEmail()
            ]);

            return true;

        } catch (\Exception $e) {
            $notification->incrementRetryCount();
            $notification->markAsFailed($e->getMessage());
            
            $this->logger->error('Failed to send notification', [
                'id' => $notification->getId(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie un email de notification
     */
    private function sendEmailNotification(EventNotification $notification): void
    {
        $event = $notification->getEvent();
        $user = $notification->getUser();

        $email = (new Email())
            ->from('noreply@musehub.com')
            ->to($user->getEmail())
            ->subject($this->getEmailSubject($notification->getType(), $event))
            ->html($this->getEmailContent($notification));

        $this->mailer->send($email);
    }

    /**
     * Génère le sujet de l'email
     */
    private function getEmailSubject(string $type, Event $event): string
    {
        return match($type) {
            'reminder_24h' => "Rappel : {$event->getTitle()} demain",
            'reminder_1h' => "C'est bientôt ! {$event->getTitle()} dans 1 heure",
            'event_created' => "Nouvelle inscription : {$event->getTitle()}",
            'event_updated' => "Mise à jour : {$event->getTitle()}",
            'event_cancelled' => "Annulation : {$event->getTitle()}",
            default => "Notification MuseHub"
        };
    }

    /**
     * Génère le contenu de l'email
     */
    private function getEmailContent(EventNotification $notification): string
    {
        $event = $notification->getEvent();
        $user = $notification->getUser();
        $type = $notification->getType();

        $eventDate = $event->getDateTime()->format('d/m/Y à H:i');
        $location = $event->getLocation() === 'online' ? 'En ligne' : 'Sur place';
        
        $eventType = $event->getEventType() 
            ? '<strong>Type :</strong> ' . $event->getEventType()->getName() . '<br>'
            : '';

        $greeting = match($type) {
            'reminder_24h' => "L'événement auquel vous êtes inscrit aura lieu demain !",
            'reminder_1h' => "L'événement commence dans 1 heure !",
            'event_created' => "Votre inscription a été confirmée.",
            'event_updated' => "L'événement a été mis à jour.",
            'event_cancelled' => "Nous sommes désolés, l'événement a été annulé.",
            default => "Notification concernant votre événement"
        };

        $html = <<<HTML
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #8b5cf6;">MuseHub - Notification Événement</h2>
                
                <p>Bonjour {$user->getEmail()},</p>
                
                <p><strong>{$greeting}</strong></p>
                
                <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #8b5cf6;">{$event->getTitle()}</h3>
                    <p style="margin: 10px 0;">
                        <strong>📅 Date :</strong> {$eventDate}<br>
                        <strong>📍 Lieu :</strong> {$location}<br>
                        {$eventType}
                    </p>
HTML;
        
        if ($event->getDescription()) {
            $html .= '<p>' . nl2br(htmlspecialchars($event->getDescription())) . '</p>';
        }
        
        $html .= <<<HTML
                </div>
                
HTML;
        
        if ($notification->getMessage()) {
            $html .= '<p>' . nl2br(htmlspecialchars($notification->getMessage())) . '</p>';
        }
        
        $html .= <<<HTML
                
                <p style="margin-top: 30px;">
                    <a href="http://127.0.0.1:8001/events" 
                       style="background: #8b5cf6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                        Voir l'événement
                    </a>
                </p>
                
                <p style="color: #666; font-size: 12px; margin-top: 30px;">
                    Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
                </p>
            </div>
        </body>
        </html>
        HTML;
        
        return $html;
    }

    /**
     * Génère un message personnalisé
     */
    private function generateMessage(Event $event, string $type): string
    {
        return match($type) {
            'reminder_24h' => "N'oubliez pas votre événement demain : {$event->getTitle()}",
            'reminder_1h' => "L'événement {$event->getTitle()} commence dans 1 heure !",
            'event_created' => "Vous êtes inscrit à {$event->getTitle()}",
            'event_updated' => "L'événement {$event->getTitle()} a été modifié",
            'event_cancelled' => "L'événement {$event->getTitle()} a été annulé",
            default => "Notification concernant {$event->getTitle()}"
        };
    }

    /**
     * Annule toutes les notifications pour un événement
     */
    public function cancelNotificationsForEvent(Event $event): void
    {
        $this->notificationRepository->deleteByEvent($event);
        $this->em->flush();
    }

    /**
     * Récupère les statistiques
     */
    public function getStatistics(): array
    {
        return $this->notificationRepository->getStatistics();
    }
}
