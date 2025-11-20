<?php

namespace App\Command;

use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-past-events',
    description: 'Remove or deactivate past events',
)]
class CleanupPastEventsCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $now = new \DateTimeImmutable();
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('e.dateTime < :now')
            ->andWhere('e.isActive = :active')
            ->setParameter('now', $now)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($events as $event) {
            $event->setIsActive(false);
            $count++;
        }

        $this->em->flush();

        $io->success("Deactivated {$count} past events.");

        return Command::SUCCESS;
    }
}

