<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\CommentRepository")]
class Comment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "comments")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Post $post;

    #[ORM\Column(type: "string")]
    private string $commenterUuid;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: "replies")]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    private ?Comment $parentComment = null;

    #[ORM\OneToMany(mappedBy: "parentComment", targetEntity: self::class, cascade: ["remove"], orphanRemoval: true)]
    private Collection $replies;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->replies = new ArrayCollection();
    }

    // Getters & setters

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getPost(): Post
    {
        return $this->post;
    }
    public function setPost(Post $post): self
    {
        $this->post = $post;
        return $this;
    }

    public function getCommenterUuid(): string
    {
        return $this->commenterUuid;
    }
    public function setCommenterUuid(string $commenterUuid): self
    {
        $this->commenterUuid = $commenterUuid;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getParentComment(): ?Comment
    {
        return $this->parentComment;
    }
    public function setParentComment(?Comment $parentComment): self
    {
        $this->parentComment = $parentComment;
        return $this;
    }

    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(Comment $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies[] = $reply;
            $reply->setParentComment($this);
        }
        return $this;
    }

    public function removeReply(Comment $reply): self
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getParentComment() === $this) {
                $reply->setParentComment(null);
            }
        }
        return $this;
    }

    public function isReply(): bool
    {
        return $this->parentComment !== null;
    }

    public function getLevel(): int
    {
        $level = 0;
        $current = $this->parentComment;
        while ($current !== null) {
            $level++;
            $current = $current->getParentComment();
        }
        return $level;
    }
}
