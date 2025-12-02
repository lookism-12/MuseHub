<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EventNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private ParticipantRepository $participantRepository
    ) {
    }

    public function notifyParticipants(Event $event, string $subject, string $message): void
    {
        $participants = $this->participantRepository->findByEventUuid($event->getUuid());

        foreach ($participants as $participant) {
            $this->sendNotification($participant, $subject, $message);
        }
    }

    public function notifyEventCreated(Event $event): void
    {
        // This would typically send to followers or subscribers
        // For now, we'll just log it
    }

    public function notifyEventReminder(Event $event): void
    {
        $subject = "Rappel: {$event->getTitle()}";
        $message = "L'événement {$event->getTitle()} aura lieu le {$event->getDateTime()->format('d/m/Y à H:i')}";
        
        $this->notifyParticipants($event, $subject, $message);
    }

    private function sendNotification(Participant $participant, string $subject, string $message): void
    {
        // In a real app, you'd fetch user email by UUID
        // For now, we'll simulate the email sending
        try {
            $email = (new Email())
                ->from('noreply@musehub.com')
                ->to('participant@example.com') // Would be fetched from user UUID
                ->subject($subject)
                ->text($message);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't break the flow
            error_log("Failed to send notification: " . $e->getMessage());
        }
    }
}

