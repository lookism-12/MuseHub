<?php

namespace App\Command;

use App\Entity\Participant;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-participant',
    description: 'Tester la création d\'un participant et les notifications',
)]
class TestParticipantCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('eventId', InputArgument::REQUIRED, 'Event ID')
            ->addArgument('userEmail', InputArgument::OPTIONAL, 'User email', 'admin@musehub.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $eventId = $input->getArgument('eventId');
        $userEmail = $input->getArgument('userEmail');

        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            $io->error("Événement ID $eventId introuvable");
            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        if (!$user) {
            $io->error("Utilisateur $userEmail introuvable");
            return Command::FAILURE;
        }

        $io->info("Création d'un participant pour:");
        $io->text("  - Événement: {$event->getTitle()} (ID: {$event->getId()})");
        $io->text("  - Utilisateur: {$user->getEmail()}");
        $io->text("  - Date événement: " . $event->getDateTime()->format('Y-m-d H:i:s'));

        $participant = new Participant();
        $participant->setEventUuid($event->getUuid());
        $participant->setParticipantUuid($user->getUuid());
        $participant->setStatus('confirmed');

        $io->text("\nPersist + Flush...");
        $this->em->persist($participant);
        $this->em->flush();

        $io->success("Participant créé avec ID: {$participant->getId()}");

        // Vérifier les notifications créées
        $io->section("Vérification des notifications");
        $sql = "SELECT id, type, status, scheduled_at FROM event_notification WHERE event_id = ? AND user_id = ? ORDER BY id DESC";
        $stmt = $this->em->getConnection()->prepare($sql);
        $result = $stmt->executeQuery([$event->getId(), $user->getId()]);
        $notifications = $result->fetchAllAssociative();

        if (empty($notifications)) {
            $io->warning("❌ AUCUNE notification créée! Le subscriber ne s'est pas déclenché.");
        } else {
            $count = count($notifications);
            $io->success("✓ {$count} notification(s) créée(s):");
            foreach ($notifications as $n) {
                $io->text("  - ID {$n['id']}: {$n['type']} - {$n['status']} (planifiée: {$n['scheduled_at']})");
            }
        }

        return Command::SUCCESS;
    }
}
