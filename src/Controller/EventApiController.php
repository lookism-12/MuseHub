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

    #[Route('/search', name: 'api_events_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        // Récupérer les paramètres de recherche
        $query = $request->query->get('q', '');
        $location = $request->query->get('location', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');
        $type = $request->query->get('type', '');
        $sort = $request->query->get('sort', 'date_asc');
        $isActive = $request->query->get('is_active', '1') === '1';
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = min(100, max(1, (int)$request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Construire la requête
        $qb = $this->eventRepository->createQueryBuilder('e');

        // Filtrer par recherche texte (titre + description)
        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.title)', ':query'),
                    $qb->expr()->like('LOWER(e.description)', ':query')
                )
            )->setParameter('query', '%' . strtolower($query) . '%');
        }

        // Filtrer par localisation
        if ($location) {
            $qb->andWhere('e.location = :location')->setParameter('location', $location);
        }

        // Filtrer par date de début
        if ($dateFrom) {
            $qb->andWhere('e.dateTime >= :from')->setParameter('from', new \DateTimeImmutable($dateFrom));
        }

        // Filtrer par date de fin
        if ($dateTo) {
            $qb->andWhere('e.dateTime <= :to')->setParameter('to', new \DateTimeImmutable($dateTo));
        }

        // Filtrer par type d'événement
        if ($type) {
            $qb->leftJoin('e.eventType', 'et')
               ->andWhere('et.name = :type')->setParameter('type', $type);
        }

        // Filtrer par statut actif
        if ($isActive) {
            $qb->andWhere('e.isActive = :active')->setParameter('active', true);
        }

        // Appliquer le tri
        $sortMap = [
            'date_asc' => ['e.dateTime', 'ASC'],
            'date_desc' => ['e.dateTime', 'DESC'],
            'title_asc' => ['e.title', 'ASC'],
            'title_desc' => ['e.title', 'DESC'],
            'created_asc' => ['e.createdAt', 'ASC'],
            'created_desc' => ['e.createdAt', 'DESC'],
        ];

        if (isset($sortMap[$sort])) {
            $qb->orderBy($sortMap[$sort][0], $sortMap[$sort][1]);
        } else {
            $qb->orderBy('e.dateTime', 'ASC');
        }

        // Compter le total
        $total = (clone $qb)->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        // Appliquer la pagination
        $events = $qb->setFirstResult($offset)
                     ->setMaxResults($limit)
                     ->getQuery()
                     ->getResult();

        // Formater les résultats
        $data = array_map(function (Event $event) {
            return [
                'id' => $event->getId(),
                'uuid' => $event->getUuid(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'date_time' => $event->getDateTime()->format('Y-m-d H:i:s'),
                'location' => $event->getLocation(),
                'organiser_uuid' => $event->getOrganiserUuid(),
                'is_active' => $event->isActive(),
                'event_type' => $event->getEventType()?->getName(),
                'participants_count' => count($this->participantRepository->findByEventUuid($event->getUuid())),
                'ics_url' => '/api/events/' . $event->getId() . '/ics',
            ];
        }, $events);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit),
            ],
            'filters_applied' => [
                'query' => $query ?: null,
                'location' => $location ?: null,
                'date_range' => ($dateFrom || $dateTo) ? $dateFrom . ' to ' . $dateTo : null,
                'type' => $type ?: null,
                'sort' => $sort,
                'is_active' => $isActive,
            ],
        ]);
    }

    #[Route('/map', name: 'api_events_map', methods: ['GET'])]
    public function map(Request $request): JsonResponse
    {
        // Paramètres optionnels
        $location = $request->query->get('location', '');
        $isActive = $request->query->get('is_active', '1') === '1';
        $limit = min(500, max(1, (int)$request->query->get('limit', 100)));

        $qb = $this->eventRepository->createQueryBuilder('e');

        // Récupérer seulement les événements avec coordonnées GPS
        $qb->andWhere('e.latitude IS NOT NULL')
           ->andWhere('e.longitude IS NOT NULL');

        // Filtrer par lieu optionnellement
        if ($location) {
            $qb->andWhere('e.location = :location')->setParameter('location', $location);
        }

        // Filtrer par statut actif
        if ($isActive) {
            $qb->andWhere('e.isActive = :active')->setParameter('active', true);
        }

        $qb->orderBy('e.dateTime', 'ASC')
           ->setMaxResults($limit);

        $events = $qb->getQuery()->getResult();

        // Formater les résultats pour une carte
        $data = array_map(function (Event $event) {
            return [
                'id' => $event->getId(),
                'uuid' => $event->getUuid(),
                'title' => $event->getTitle(),
                'latitude' => $event->getLatitude(),
                'longitude' => $event->getLongitude(),
                'location' => $event->getLocation(),
                'date_time' => $event->getDateTime()->format('Y-m-d H:i:s'),
                'organiser_uuid' => $event->getOrganiserUuid(),
                'participants_count' => count($this->participantRepository->findByEventUuid($event->getUuid())),
                'map_url' => 'https://maps.google.com/?q=' . $event->getLatitude() . ',' . $event->getLongitude(),
            ];
        }, $events);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'filters' => [
                'location' => $location ?: null,
                'is_active' => $isActive,
            ],
        ]);
    }

    #[Route('/{id}/map', name: 'api_events_map_detail', methods: ['GET'])]
    public function mapDetail(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        // Vérifier si l'événement a des coordonnées GPS
        if ($event->getLatitude() === null || $event->getLongitude() === null) {
            return new JsonResponse([
                'error' => 'Event has no GPS coordinates',
                'message' => 'This event does not have geolocation data',
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $event->getId(),
                'uuid' => $event->getUuid(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'latitude' => $event->getLatitude(),
                'longitude' => $event->getLongitude(),
                'location_name' => $event->getLocation(),
                'date_time' => $event->getDateTime()->format('Y-m-d H:i:s'),
                'organiser_uuid' => $event->getOrganiserUuid(),
                'is_active' => $event->isActive(),
                'event_type' => $event->getEventType()?->getName(),
                'participants_count' => count($this->participantRepository->findByEventUuid($event->getUuid())),
                'google_maps_url' => 'https://maps.google.com/?q=' . $event->getLatitude() . ',' . $event->getLongitude(),
                'openstreetmap_url' => 'https://www.openstreetmap.org/?mlat=' . $event->getLatitude() . '&mlon=' . $event->getLongitude() . '&zoom=15',
            ],
        ]);
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

    #[Route('/{id}/ics', name: 'api_events_ics', methods: ['GET'])]
    public function exportIcs(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        // Générer le contenu iCalendar
        $ics = $this->generateIcsContent($event);

        // Créer une réponse binaire stricte
        $response = new Response();
        $response->setContent($ics);
        $response->headers->set('Content-Type', 'text/calendar');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->sanitizeFilename($event->getTitle()) . '.ics"');
        $response->setStatusCode(200);

        return $response;
    }

    /**
     * Génère un fichier iCalendar RFC 5545 compliant
     */
    private function generateIcsContent(Event $event): string
    {
        // Dates au format UTC (RFC 5545)
        $dtstart = $event->getDateTime()->format('Ymd\THis\Z');
        $dtend = $event->getDateTime()->add(new \DateInterval('PT2H'))->format('Ymd\THis\Z');
        $dtstamp = (new \DateTimeImmutable())->format('Ymd\THis\Z');
        
        // Nettoyer les textes
        $summary = htmlspecialchars($event->getTitle(), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($event->getDescription() ?? '', ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars(
            $event->getLocation() === 'online' ? 'En ligne' : ($event->getLocation() ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
        $uid = $event->getUuid() . '@musehub.local';
        
        // Construire le fichier iCalendar
        $ics = <<<'ICS'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//MuseHub//MuseHub Calendar//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
UID:{$uid}
DTSTAMP:{$dtstamp}
DTSTART:{$dtstart}
DTEND:{$dtend}
SUMMARY:{$summary}
DESCRIPTION:{$description}
LOCATION:{$location}
URL:http://127.0.0.1:8001/events
STATUS:CONFIRMED
SEQUENCE:0
END:VEVENT
END:VCALENDAR
ICS;

        // Interpoler les variables
        return str_replace(
            ['{$uid}', '{$dtstamp}', '{$dtstart}', '{$dtend}', '{$summary}', '{$description}', '{$location}'],
            [$uid, $dtstamp, $dtstart, $dtend, $summary, $description, $location],
            $ics
        );
    }

    /**
     * Nettoie le nom du fichier
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remplacer les caractères non alphanumériques
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = substr($filename, 0, 100); // Limiter la longueur
        
        return $filename ?: 'event';
    }
}