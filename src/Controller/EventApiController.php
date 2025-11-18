<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participant;
use App\Repository\EventRepository;
use App\Repository\ParticipantRepository;
use App\Service\EventNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events')]
class EventApiController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private ParticipantRepository $participantRepository,
        private EntityManagerInterface $em,
        private EventNotifier $eventNotifier
    ) {
    }

    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        if ($dateFrom && $dateTo) {
            $start = new \DateTimeImmutable($dateFrom);
            $end = new \DateTimeImmutable($dateTo);
            $events = $this->eventRepository->findByDateRange($start, $end);
        } else {
            $events = $this->eventRepository->findUpcoming();
        }

        $data = array_map(function (Event $event) {
            return [
                'id' => $event->getId(),
                'uuid' => $event->getUuid(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'date_time' => $event->getDateTime()->format('Y-m-d H:i:s'),
                'location' => $event->getLocation(),
                'organiser_uuid' => $event->getOrganiserUuid(),
            ];
        }, $events);

        return new JsonResponse($data);
    }

    #[Route('', name: 'api_events_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['date_time'])) {
            return new JsonResponse(['error' => 'Title and date_time are required'], Response::HTTP_BAD_REQUEST);
        }

        // Only artists and admins can create events
        if (!$this->isGranted('ROLE_ARTIST') && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Only artists and admins can create events'], Response::HTTP_FORBIDDEN);
        }

        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description'] ?? null);
        $event->setDateTime(new \DateTimeImmutable($data['date_time']));
        $event->setLocation($data['location'] ?? 'online');
        $event->setOrganiserUuid($user->getUuid());

        $this->em->persist($event);
        $this->em->flush();

        $this->eventNotifier->notifyEventCreated($event);

        return new JsonResponse([
            'id' => $event->getId(),
            'uuid' => $event->getUuid(),
            'message' => 'Event created successfully',
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_events_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Only organizer or admin can update
        if ($event->getOrganiserUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Not authorized to update this event'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['date_time'])) {
            $event->setDateTime(new \DateTimeImmutable($data['date_time']));
        }
        if (isset($data['location'])) {
            $event->setLocation($data['location']);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $event->getId(),
            'uuid' => $event->getUuid(),
            'message' => 'Event updated successfully',
        ]);
    }

    #[Route('/{id}', name: 'api_events_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Only organizer or admin can delete
        if ($event->getOrganiserUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Not authorized to delete this event'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($event);
        $this->em->flush();

        return new JsonResponse(['message' => 'Event deleted successfully']);
    }

    #[Route('/{id}/join', name: 'api_events_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Check if already registered
        $existing = $this->participantRepository->findExisting($event->getUuid(), $user->getUuid());
        if ($existing) {
            return new JsonResponse(['error' => 'Already registered for this event'], Response::HTTP_CONFLICT);
        }

        $participant = new Participant();
        $participant->setEventUuid($event->getUuid());
        $participant->setParticipantUuid($user->getUuid());
        $participant->setStatus('confirmed');

        $this->em->persist($participant);
        $this->em->flush();

        return new JsonResponse(['message' => 'Successfully joined event'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/leave', name: 'api_events_leave', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function leave(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $participant = $this->participantRepository->findExisting($event->getUuid(), $user->getUuid());

        if (!$participant) {
            return new JsonResponse(['error' => 'Not registered for this event'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($participant);
        $this->em->flush();

        return new JsonResponse(['message' => 'Successfully left event']);
    }
}

