<?php

namespace App\Entity;

use App\Repository\ArtworkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtworkRepository::class)]
class Artwork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    private string $artistUuid;

    #[ORM\Column(length: 32)]
    private string $status = 'visible';

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'artworks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\OneToMany(mappedBy: 'artwork', targetEntity: ArtworkLike::class, orphanRemoval: true)]
    private $likes;

    public function __construct()
    {
        $this->likes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->status = 'visible';
    }

    public function getLikesCount(): ?int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): self
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function incrementLikesCount(): self
    {
        $this->likesCount++;
        return $this;
    }

    public function decrementLikesCount(): self
    {
        $this->likesCount = max(0, $this->likesCount - 1);
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, ArtworkLike>
     */
    public function getLikes(): \Doctrine\Common\Collections\Collection
    {
        return $this->likes;
    }

    public function addLike(ArtworkLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setArtwork($this);
        }

        return $this;
    }

    public function removeLike(ArtworkLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getArtwork() === $this) {
                $like->setArtwork(null);
            }
        }

        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getArtistUuid(): string
    {
        return $this->artistUuid;
    }

    public function setArtistUuid(string $artistUuid): self
    {
        $this->artistUuid = $artistUuid;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }
}
