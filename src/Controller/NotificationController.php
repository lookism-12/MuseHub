<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationService $notificationService,
        private UserRepository $userRepository
    ) {
    }

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $notifications = $this->notificationRepository->findByUser($user->getUuid(), $limit, $offset);

        // Resolve actor names
        $actorUuids = array_unique(array_map(fn($n) => $n->getActorUuid(), $notifications));
        $actors = $this->userRepository->createQueryBuilder('u')
            ->where('u.uuid IN (:uuids)')
            ->setParameter('uuids', $actorUuids)
            ->getQuery()
            ->getResult();

        $actorNames = [];
        foreach ($actors as $actor) {
            $actorNames[$actor->getUuid()] = $actor->getUsername() ?: $actor->getEmail();
        }

        $data = array_map(function ($notification) use ($actorNames) {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'actor_uuid' => $notification->getActorUuid(),
                'actor_name' => $actorNames[$notification->getActorUuid()] ?? 'Unknown User',
                'post_id' => $notification->getPostId(),
                'comment_id' => $notification->getCommentId(),
                'is_read' => $notification->isRead(),
                'created_at' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return new JsonResponse([
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->notificationRepository->countUnreadByUser($user->getUuid());

        return new JsonResponse(['unread_count' => $count]);
    }

    #[Route('/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return new JsonResponse(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
        }

        // Ensure user owns this notification
        if ($notification->getRecipientUuid() !== $user->getUuid()) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->notificationService->markAsRead($notification);

        return new JsonResponse([
            'message' => 'Notification marked as read',
            'id' => $notification->getId(),
        ]);
    }

    #[Route('/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->notificationService->markAllAsReadForUser($user->getUuid());

        return new JsonResponse([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }
}
