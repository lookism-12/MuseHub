<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\PostRepository")]
class Post
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string")]
    private string $authorUuid = '';

    #[ORM\Column(type:"text")]
    private string $content = '';

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type:"integer")]
    private int $likesCount = 0;

    #[ORM\Column(type:"integer")]
    private int $dislikesCount = 0;

    #[ORM\OneToMany(mappedBy: "post", targetEntity: Comment::class, cascade:["remove"], orphanRemoval:true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: "post", targetEntity: PostReaction::class, cascade: ["remove"], orphanRemoval: true)]
    private Collection $reactions;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters & setters

    public function getId(): ?int { return $this->id; }
    public function getAuthorUuid(): string { return $this->authorUuid; }
    public function setAuthorUuid(string $authorUuid): self { $this->authorUuid = $authorUuid; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getLikesCount(): int { return $this->likesCount; }
    public function setLikesCount(int $count): self { $this->likesCount = max(0, $count); return $this; }
    public function incrementLikes(): self { $this->likesCount++; return $this; }
    public function decrementLikes(): self { $this->likesCount = max(0, $this->likesCount - 1); return $this; }

    public function getDislikesCount(): int { return $this->dislikesCount; }
    public function setDislikesCount(int $count): self { $this->dislikesCount = max(0, $count); return $this; }
    public function incrementDislikes(): self { $this->dislikesCount++; return $this; }
    public function decrementDislikes(): self { $this->dislikesCount = max(0, $this->dislikesCount - 1); return $this; }

    public function getComments(): Collection { return $this->comments; }
    public function getReactions(): Collection { return $this->reactions; }
}
