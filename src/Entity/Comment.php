<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\CommentRepository")]
class Comment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "comments")]
    #[ORM\JoinColumn(nullable: false, onDelete:"CASCADE")]
    private Post $post;

    #[ORM\Column(type:"string")]
    private string $commenterUuid;

    #[ORM\Column(type:"text")]
    private string $content;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters & setters

    public function getId(): ?int { return $this->id; }
    public function getPost(): Post { return $this->post; }
    public function setPost(Post $post): self { $this->post = $post; return $this; }

    public function getCommenterUuid(): string { return $this->commenterUuid; }
    public function setCommenterUuid(string $commenterUuid): self { $this->commenterUuid = $commenterUuid; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
