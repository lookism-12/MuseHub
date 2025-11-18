<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class EventDashboardController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private ParticipantRepository $participantRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'admin_events_list', methods: ['GET'])]
    public function list(): Response
    {
        $upcoming = $this->eventRepository->findUpcoming();
        $all = $this->eventRepository->findAll();

        $stats = [
            'total' => count($all),
            'upcoming' => count($upcoming),
            'past' => count($all) - count($upcoming),
        ];

        return $this->render('event/admin_list.html.twig', [
            'events' => $all,
            'upcoming' => $upcoming,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/participants', name: 'admin_events_participants', methods: ['GET'])]
    public function participants(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            $this->addFlash('error', 'Event not found');
            return $this->redirectToRoute('admin_events_list');
        }

        $participants = $this->participantRepository->findByEventUuid($event->getUuid());

        return $this->render('event/admin_participants.html.twig', [
            'event' => $event,
            'participants' => $participants,
        ]);
    }
}

