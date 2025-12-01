<?php

namespace App\Controller;

use App\Entity\EventType;
use App\Repository\EventTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/event-types')]
#[IsGranted('ROLE_ADMIN')]
class EventTypeDashboardController extends AbstractController
{
    public function __construct(
        private EventTypeRepository $eventTypeRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'admin_event_types_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $statusFilter = $request->query->get('status', 'all');
        $search = trim((string)$request->query->get('q', ''));

        $qb = $this->eventTypeRepository->createQueryBuilder('et');

        if ($statusFilter === 'active') {
            $qb->andWhere('et.is_active = :active')->setParameter('active', true);
        } elseif ($statusFilter === 'inactive') {
            $qb->andWhere('et.is_active = :active')->setParameter('active', false);
        } elseif ($statusFilter === 'paid') {
            $qb->andWhere('et.requires_payment = :paid')->setParameter('paid', true);
        } elseif ($statusFilter === 'free') {
            $qb->andWhere('et.requires_payment = :paid')->setParameter('paid', false);
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(et.name) LIKE :search OR LOWER(et.description) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $eventTypes = $qb->orderBy('et.sort_order', 'ASC')
                         ->addOrderBy('et.name', 'ASC')
                         ->getQuery()
                         ->getResult();

        $all = $this->eventTypeRepository->findAll();
        $active = $this->eventTypeRepository->findAllActive();

        $stats = [
            'total' => count($all),
            'active' => count($active),
            'inactive' => count($all) - count($active),
            'paid' => count(array_filter($all, fn($t) => $t->isRequiresPayment())),
            'free' => count(array_filter($all, fn($t) => !$t->isRequiresPayment())),
        ];

        return $this->render('event_type/admin_list.html.twig', [
            'eventTypes' => $eventTypes,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'admin_event_types_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $eventType = new EventType();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_event_type', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_event_types_new');
            }

            $name = trim((string)$request->request->get('name'));

            if (!$name) {
                $this->addFlash('error', 'Le nom est requis.');
                return $this->redirectToRoute('admin_event_types_new');
            }

            $eventType->setName($name);
            $eventType->setDescription($request->request->get('description') ?: null);
            $eventType->setCapacityType($request->request->get('capacity_type', 'unlimited'));
            $eventType->setDefaultMaxParticipants($request->request->get('default_max_participants') ? (int)$request->request->get('default_max_participants') : null);
            $eventType->setTypicalDurationHours($request->request->get('typical_duration_hours') ? (int)$request->request->get('typical_duration_hours') : null);
            $eventType->setRequiresPayment($request->request->getBoolean('requires_payment', false));
            $eventType->setCertificateEnabled($request->request->getBoolean('certificate_enabled', false));
            $eventType->setRecordingEnabled($request->request->getBoolean('recording_enabled', false));
            $eventType->setAllowedLocation($request->request->get('allowed_location', 'both'));
            $eventType->setVisibility($request->request->get('visibility', 'public'));
            $eventType->setIsActive($request->request->getBoolean('is_active', true));
            $eventType->setSortOrder($request->request->get('sort_order') ? (int)$request->request->get('sort_order') : 0);

            $this->em->persist($eventType);
            $this->em->flush();

            $this->addFlash('success', 'Type d\'événement créé avec succès.');
            return $this->redirectToRoute('admin_event_types_list');
        }

        return $this->render('event_type/admin_form.html.twig', [
            'eventType' => $eventType,
            'action' => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_types_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $eventType = $this->eventTypeRepository->find($id);
        if (!$eventType) {
            $this->addFlash('error', 'Type d\'événement introuvable.');
            return $this->redirectToRoute('admin_event_types_list');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_event_type_' . $id, (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_event_types_edit', ['id' => $id]);
            }

            $name = trim((string)$request->request->get('name'));

            if (!$name) {
                $this->addFlash('error', 'Le nom est requis.');
                return $this->redirectToRoute('admin_event_types_edit', ['id' => $id]);
            }

            $eventType->setName($name);
            $eventType->setDescription($request->request->get('description') ?: null);
            $eventType->setCapacityType($request->request->get('capacity_type', 'unlimited'));
            $eventType->setDefaultMaxParticipants($request->request->get('default_max_participants') ? (int)$request->request->get('default_max_participants') : null);
            $eventType->setTypicalDurationHours($request->request->get('typical_duration_hours') ? (int)$request->request->get('typical_duration_hours') : null);
            $eventType->setRequiresPayment($request->request->getBoolean('requires_payment', false));
            $eventType->setCertificateEnabled($request->request->getBoolean('certificate_enabled', false));
            $eventType->setRecordingEnabled($request->request->getBoolean('recording_enabled', false));
            $eventType->setAllowedLocation($request->request->get('allowed_location', 'both'));
            $eventType->setVisibility($request->request->get('visibility', 'public'));
            $eventType->setIsActive($request->request->getBoolean('is_active', true));
            $eventType->setSortOrder($request->request->get('sort_order') ? (int)$request->request->get('sort_order') : 0);

            $this->em->flush();
            $this->addFlash('success', 'Type d\'événement mis à jour avec succès.');

            return $this->redirectToRoute('admin_event_types_list');
        }

        return $this->render('event_type/admin_form.html.twig', [
            'eventType' => $eventType,
            'action' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_event_types_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $eventType = $this->eventTypeRepository->find($id);
        if (!$eventType) {
            $this->addFlash('error', 'Type d\'événement introuvable.');
            return $this->redirectToRoute('admin_event_types_list');
        }

        if (!$this->isCsrfTokenValid('delete_event_type_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_event_types_list');
        }

        // Soft delete - désactiver au lieu de supprimer
        $eventType->setIsActive(false);
        $this->em->flush();

        $this->addFlash('success', 'Type d\'événement désactivé avec succès.');
        return $this->redirectToRoute('admin_event_types_list');
    }

    #[Route('/{id}/toggle', name: 'admin_event_types_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        $eventType = $this->eventTypeRepository->find($id);
        if (!$eventType) {
            $this->addFlash('error', 'Type d\'événement introuvable.');
            return $this->redirectToRoute('admin_event_types_list');
        }

        if (!$this->isCsrfTokenValid('toggle_event_type_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_event_types_list');
        }

        $eventType->setIsActive(!$eventType->isActive());
        $this->em->flush();

        $status = $eventType->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Type d'événement {$status} avec succès.");
        
        return $this->redirectToRoute('admin_event_types_list');
    }
}
