<?php

namespace App\Controller;

use App\Entity\EventType;
use App\Repository\EventTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/event-types', name: 'api_event_types_')]
class EventTypeApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EventTypeRepository $repository, Request $request): JsonResponse
    {
        $active = $request->query->get('active');
        $capacityType = $request->query->get('capacity_type');
        $paid = $request->query->get('paid');
        
        if ($capacityType) {
            $eventTypes = $repository->findByCapacityType($capacityType);
        } elseif ($paid !== null) {
            $eventTypes = $repository->findPaidEventTypes();
        } elseif ($active !== null) {
            $eventTypes = $repository->findAllActive();
        } else {
            $eventTypes = $repository->findBy([], ['sort_order' => 'ASC', 'name' => 'ASC']);
        }
        
        $data = array_map(function (EventType $type) {
            return [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'description' => $type->getDescription(),
                'icon' => $type->getIcon(),
                'color' => $type->getColor(),
                'capacity_type' => $type->getCapacityType(),
                'default_max_participants' => $type->getDefaultMaxParticipants(),
                'typical_duration_hours' => $type->getTypicalDurationHours(),
                'requires_payment' => $type->isRequiresPayment(),
                'certificate_enabled' => $type->isCertificateEnabled(),
                'recording_enabled' => $type->isRecordingEnabled(),
                'allowed_location' => $type->getAllowedLocation(),
                'visibility' => $type->getVisibility(),
                'is_active' => $type->isActive(),
                'events_count' => $type->getEvents()->count()
            ];
        }, $eventTypes);
        
        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }
    
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(EventType $eventType): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $eventType->getId(),
                'name' => $eventType->getName(),
                'description' => $eventType->getDescription(),
                'icon' => $eventType->getIcon(),
                'color' => $eventType->getColor(),
                'capacity_type' => $eventType->getCapacityType(),
                'default_max_participants' => $eventType->getDefaultMaxParticipants(),
                'typical_duration_hours' => $eventType->getTypicalDurationHours(),
                'requires_payment' => $eventType->isRequiresPayment(),
                'certificate_enabled' => $eventType->isCertificateEnabled(),
                'recording_enabled' => $eventType->isRecordingEnabled(),
                'allowed_location' => $eventType->getAllowedLocation(),
                'visibility' => $eventType->getVisibility(),
                'is_active' => $eventType->isActive(),
                'events_count' => $eventType->getEvents()->count()
            ]
        ]);
    }
    
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name'])) {
            return $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $eventType = new EventType();
        $eventType->setName($data['name']);
        $eventType->setDescription($data['description'] ?? null);
        $eventType->setCapacityType($data['capacity_type'] ?? 'unlimited');
        $eventType->setDefaultMaxParticipants($data['default_max_participants'] ?? null);
        $eventType->setTypicalDurationHours($data['typical_duration_hours'] ?? null);
        $eventType->setRequiresPayment($data['requires_payment'] ?? false);
        $eventType->setCertificateEnabled($data['certificate_enabled'] ?? false);
        $eventType->setRecordingEnabled($data['recording_enabled'] ?? false);
        $eventType->setAllowedLocation($data['allowed_location'] ?? 'both');
        $eventType->setVisibility($data['visibility'] ?? 'public');
        $eventType->setIsActive($data['is_active'] ?? true);
        $eventType->setSortOrder($data['sort_order'] ?? 0);
        
        $em->persist($eventType);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Event type created successfully',
            'data' => [
                'id' => $eventType->getId(),
                'name' => $eventType->getName()
            ]
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(EventType $eventType, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) $eventType->setName($data['name']);
        if (isset($data['description'])) $eventType->setDescription($data['description']);
        if (isset($data['capacity_type'])) $eventType->setCapacityType($data['capacity_type']);
        if (isset($data['default_max_participants'])) $eventType->setDefaultMaxParticipants($data['default_max_participants']);
        if (isset($data['typical_duration_hours'])) $eventType->setTypicalDurationHours($data['typical_duration_hours']);
        if (isset($data['requires_payment'])) $eventType->setRequiresPayment($data['requires_payment']);
        if (isset($data['certificate_enabled'])) $eventType->setCertificateEnabled($data['certificate_enabled']);
        if (isset($data['recording_enabled'])) $eventType->setRecordingEnabled($data['recording_enabled']);
        if (isset($data['allowed_location'])) $eventType->setAllowedLocation($data['allowed_location']);
        if (isset($data['visibility'])) $eventType->setVisibility($data['visibility']);
        if (isset($data['is_active'])) $eventType->setIsActive($data['is_active']);
        if (isset($data['sort_order'])) $eventType->setSortOrder($data['sort_order']);
        
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Event type updated successfully'
        ]);
    }
    
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(EventType $eventType, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Soft delete - just deactivate
        $eventType->setIsActive(false);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Event type deactivated successfully'
        ]);
    }
    
    #[Route('/stats/summary', name: 'stats', methods: ['GET'])]
    public function stats(EventTypeRepository $repository): JsonResponse
    {
        $allTypes = $repository->findAll();
        
        $stats = [
            'total' => count($allTypes),
            'active' => 0,
            'paid' => 0,
            'free' => 0,
            'with_certificate' => 0,
            'by_capacity_type' => [
                'unlimited' => 0,
                'limited' => 0,
                'invite_only' => 0
            ],
            'by_location' => [
                'online' => 0,
                'offline' => 0,
                'both' => 0
            ]
        ];
        
        foreach ($allTypes as $type) {
            if ($type->isActive()) $stats['active']++;
            if ($type->isRequiresPayment()) {
                $stats['paid']++;
            } else {
                $stats['free']++;
            }
            if ($type->isCertificateEnabled()) $stats['with_certificate']++;
            
            $stats['by_capacity_type'][$type->getCapacityType()]++;
            $stats['by_location'][$type->getAllowedLocation()]++;
        }
        
        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
