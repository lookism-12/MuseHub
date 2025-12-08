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
    public function list(Request $request): Response
    {
        $statusFilter = $request->query->get('status', 'all');
        $search = trim((string)$request->query->get('q', ''));

        $qb = $this->eventRepository->createQueryBuilder('e');

        $now = new \DateTimeImmutable();
        if ($statusFilter === 'upcoming') {
            $qb->andWhere('e.dateTime >= :now')->setParameter('now', $now);
        } elseif ($statusFilter === 'past') {
            $qb->andWhere('e.dateTime < :now')->setParameter('now', $now);
        } elseif ($statusFilter === 'active') {
            $qb->andWhere('e.isActive = :active')->setParameter('active', true);
        } elseif ($statusFilter === 'inactive') {
            $qb->andWhere('e.isActive = :active')->setParameter('active', false);
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(e.title) LIKE :search OR LOWER(e.location) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $events = $qb->orderBy('e.dateTime', 'DESC')->getQuery()->getResult();

        $upcoming = $this->eventRepository->findUpcoming();
        $all = $this->eventRepository->findAll();

        $stats = [
            'total' => count($all),
            'upcoming' => count($upcoming),
            'past' => count($all) - count($upcoming),
        ];

        return $this->render('event/admin_list.html.twig', [
            'events' => $events,
            'upcoming' => $upcoming,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'admin_events_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $event = new Event();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_event', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_events_new');
            }

            $title = trim((string)$request->request->get('title'));
            $date = $request->request->get('date_time');
            $location = $request->request->get('location', 'online');
            $organiserUuid = $request->request->get('organiser_uuid') ?: ($this->getUser()?->getUuid());

            if (!$title || !$date || !$organiserUuid) {
                $this->addFlash('error', 'Titre, date et organisateur sont requis.');
                return $this->redirectToRoute('admin_events_new');
            }

            try {
                $event->setDateTime(new \DateTimeImmutable($date));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide.');
                return $this->redirectToRoute('admin_events_new');
            }

            $event->setTitle($title);
            $event->setDescription($request->request->get('description') ?: null);
            $event->setLocation($location);
            $event->setOrganiserUuid($organiserUuid);
            $event->setIsActive($request->request->getBoolean('is_active', true));

            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', 'Événement créé.');
            return $this->redirectToRoute('admin_events_list');
        }

        return $this->render('event/admin_form.html.twig', [
            'event' => $event,
            'action' => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('admin_events_list');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_event_' . $id, (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_events_edit', ['id' => $id]);
            }

            $title = trim((string)$request->request->get('title'));
            $date = $request->request->get('date_time');

            if (!$title || !$date) {
                $this->addFlash('error', 'Titre et date sont requis.');
                return $this->redirectToRoute('admin_events_edit', ['id' => $id]);
            }

            try {
                $event->setDateTime(new \DateTimeImmutable($date));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide.');
                return $this->redirectToRoute('admin_events_edit', ['id' => $id]);
            }

            $event->setTitle($title);
            $event->setDescription($request->request->get('description') ?: null);
            $event->setLocation($request->request->get('location', 'online'));
            $event->setOrganiserUuid($request->request->get('organiser_uuid') ?: $event->getOrganiserUuid());
            $event->setIsActive($request->request->getBoolean('is_active', true));

            $this->em->flush();
            $this->addFlash('success', 'Événement mis à jour.');

            return $this->redirectToRoute('admin_events_list');
        }

        return $this->render('event/admin_form.html.twig', [
            'event' => $event,
            'action' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_events_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('admin_events_list');
        }

        if (!$this->isCsrfTokenValid('delete_event_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_events_list');
        }

        $this->em->remove($event);
        $this->em->flush();

        $this->addFlash('success', 'Événement supprimé.');
        return $this->redirectToRoute('admin_events_list');
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

