<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_recipient_uuid', columns: ['recipient_uuid'])]
#[ORM\Index(name: 'idx_is_read', columns: ['is_read'])]
#[ORM\Index(name: 'idx_recipient_read', columns: ['recipient_uuid', 'is_read'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $recipientUuid;

    #[ORM\Column(type: 'string', length: 64)]
    private string $actorUuid;

    #[ORM\Column(type: 'string', length: 32)]
    private string $type;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $postId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $commentId = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public const TYPE_POST_REACTION = 'post_reaction';
    public const TYPE_POST_COMMENT = 'post_comment';
    public const TYPE_COMMENT_REPLY = 'comment_reply';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipientUuid(): string
    {
        return $this->recipientUuid;
    }

    public function setRecipientUuid(string $recipientUuid): self
    {
        $this->recipientUuid = $recipientUuid;
        return $this;
    }

    public function getActorUuid(): string
    {
        return $this->actorUuid;
    }

    public function setActorUuid(string $actorUuid): self
    {
        $this->actorUuid = $actorUuid;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $allowedTypes = [
            self::TYPE_POST_REACTION,
            self::TYPE_POST_COMMENT,
            self::TYPE_COMMENT_REPLY
        ];

        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid notification type: %s', $type));
        }

        $this->type = $type;
        return $this;
    }

    public function getPostId(): ?int
    {
        return $this->postId;
    }

    public function setPostId(?int $postId): self
    {
        $this->postId = $postId;
        return $this;
    }

    public function getCommentId(): ?int
    {
        return $this->commentId;
    }

    public function setCommentId(?int $commentId): self
    {
        $this->commentId = $commentId;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function markAsRead(): self
    {
        $this->isRead = true;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRelatedData(): array
    {
        return [
            'type' => $this->type,
            'post_id' => $this->postId,
            'comment_id' => $this->commentId,
        ];
    }
}
