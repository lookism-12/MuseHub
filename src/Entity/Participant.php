<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\Table(name: 'participant')]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private string $eventUuid;

    #[ORM\Column(length: 36)]
    private string $participantUuid;

    #[ORM\Column(length: 20)]
    private string $status = 'confirmed'; // 'confirmed' or 'cancelled'

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventUuid(): string
    {
        return $this->eventUuid;
    }

    public function setEventUuid(string $eventUuid): self
    {
        $this->eventUuid = $eventUuid;
        return $this;
    }

    public function getParticipantUuid(): string
    {
        return $this->participantUuid;
    }

    public function setParticipantUuid(string $participantUuid): self
    {
        $this->participantUuid = $participantUuid;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

