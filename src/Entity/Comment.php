<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\CommentRepository")]
class Comment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "comments")]
    #[ORM\JoinColumn(nullable: false, onDelete:"CASCADE")]
    private Post $post;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'])]
    private Collection $replies;

    #[ORM\Column(type:"string")]
    private string $commenterUuid;

    #[ORM\Column(type:"text")]
    private string $content;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->replies = new ArrayCollection();
    }

    // Getters & setters

    public function getId(): ?int { return $this->id; }
    public function getPost(): Post { return $this->post; }
    public function setPost(Post $post): self { $this->post = $post; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): self { $this->parent = $parent; return $this; }

    /**
     * @return Collection<int, self>
     */
    public function getReplies(): Collection { return $this->replies; }
    public function addReply(self $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }
        return $this;
    }
    public function removeReply(self $reply): self
    {
        if ($this->replies->removeElement($reply)) {
            // set the owning side to null (unless already changed)
            if ($reply->getParent() === $this) {
                $reply->setParent(null);
            }
        }
        return $this;
    }

    public function getCommenterUuid(): string { return $this->commenterUuid; }
    public function setCommenterUuid(string $commenterUuid): self { $this->commenterUuid = $commenterUuid; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
