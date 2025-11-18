<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: "App\Repository\PostRepository")]
class Post
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string")]
    private string $authorUuid;

    #[ORM\Column(type:"text")]
    private string $content;

    #[ORM\Column(type:"string", nullable:true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type:"integer")]
    private int $likesCount = 0;

    #[ORM\OneToMany(mappedBy: "post", targetEntity: Comment::class, cascade:["remove"], orphanRemoval:true)]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
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
    public function incrementLikes(): self { $this->likesCount++; return $this; }

    public function getComments(): Collection { return $this->comments; }
}
