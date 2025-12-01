<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Notification;
use App\Entity\Post;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * Create notification when someone reacts to a post
     */
    public function createPostReactionNotification(Post $post, string $actorUuid, string $reactionType): ?Notification
    {
        $recipientUuid = $post->getAuthorUuid();

        // Debug: Log notification creation attempt
        error_log("Creating notification: type=post_reaction, recipient=$recipientUuid, actor=$actorUuid, post=" . $post->getId());

        // Check for duplicate recent notification
        $existing = $this->notificationRepository->findSimilarRecent(
            $recipientUuid,
            Notification::TYPE_POST_REACTION,
            $post->getId(),
            null,
            5
        );

        if ($existing) {
            return $existing;
        }

        $notification = new Notification();
        $notification->setRecipientUuid($recipientUuid);
        $notification->setActorUuid($actorUuid);
        $notification->setType(Notification::TYPE_POST_REACTION);
        $notification->setPostId($post->getId());

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    /**
     * Create notification when someone comments on a post
     */
    public function createPostCommentNotification(Post $post, Comment $comment, string $actorUuid): ?Notification
    {
        $recipientUuid = $post->getAuthorUuid();

        // Don't create notification if this is a reply (handled separately)
        if ($comment->getParentComment() !== null) {
            return null;
        }

        // Debug: Log notification creation attempt
        error_log("Creating notification: type=post_comment, recipient=$recipientUuid, actor=$actorUuid, post=" . $post->getId() . ", comment=" . $comment->getId());

        // Check for duplicate recent notification
        $existing = $this->notificationRepository->findSimilarRecent(
            $recipientUuid,
            Notification::TYPE_POST_COMMENT,
            $post->getId(),
            null,
            5
        );

        if ($existing) {
            return $existing;
        }

        $notification = new Notification();
        $notification->setRecipientUuid($recipientUuid);
        $notification->setActorUuid($actorUuid);
        $notification->setType(Notification::TYPE_POST_COMMENT);
        $notification->setPostId($post->getId());
        $notification->setCommentId($comment->getId());

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    /**
     * Create notification when someone replies to a comment
     */
    public function createCommentReplyNotification(Comment $parentComment, Comment $reply, string $actorUuid): ?Notification
    {
        $recipientUuid = $parentComment->getCommenterUuid();

        // Don't notify guest users
        if (str_starts_with($recipientUuid, 'guest_')) {
            return null;
        }

        // Debug: Log notification creation attempt
        error_log("Creating notification: type=comment_reply, recipient=$recipientUuid, actor=$actorUuid, post=" . $parentComment->getPost()->getId() . ", parent_comment=" . $parentComment->getId() . ", reply=" . $reply->getId());

        // Check for duplicate recent notification
        $existing = $this->notificationRepository->findSimilarRecent(
            $recipientUuid,
            Notification::TYPE_COMMENT_REPLY,
            $parentComment->getPost()->getId(),
            $parentComment->getId(),
            5
        );

        if ($existing) {
            return $existing;
        }

        $notification = new Notification();
        $notification->setRecipientUuid($recipientUuid);
        $notification->setActorUuid($actorUuid);
        $notification->setType(Notification::TYPE_COMMENT_REPLY);
        $notification->setPostId($parentComment->getPost()->getId());
        $notification->setCommentId($reply->getId());

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        if (!$notification->isRead()) {
            $notification->markAsRead();
            $this->em->flush();
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsReadForUser(string $userUuid): int
    {
        return $this->notificationRepository->markAllAsReadByUser($userUuid);
    }
}
