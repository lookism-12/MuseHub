<?php

namespace App\Entity;

use App\Repository\EventTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
#[ORM\Table(name: 'event_type')]
class EventType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $name = ''; // Initialize to avoid uninitialized typed property access in forms

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $capacity_type = 'unlimited'; // unlimited, limited, invite_only

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $default_max_participants = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $typical_duration_hours = null;

    #[ORM\Column(type: 'boolean')]
    private bool $requires_payment = false;

    #[ORM\Column(type: 'boolean')]
    private bool $certificate_enabled = false;

    #[ORM\Column(type: 'boolean')]
    private bool $recording_enabled = false;

    #[ORM\Column(type: 'string', length: 20)]
    private string $allowed_location = 'both'; // online, offline, both

    #[ORM\Column(type: 'string', length: 20)]
    private string $visibility = 'public'; // public, private, members_only

    #[ORM\Column(type: 'boolean')]
    private bool $is_active = true;

    #[ORM\Column(type: 'integer')]
    private int $sort_order = 0;

    #[ORM\OneToMany(mappedBy: 'eventType', targetEntity: Event::class)]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getCapacityType(): string
    {
        return $this->capacity_type;
    }

    public function setCapacityType(string $capacity_type): self
    {
        $this->capacity_type = $capacity_type;
        return $this;
    }

    public function getDefaultMaxParticipants(): ?int
    {
        return $this->default_max_participants;
    }

    public function setDefaultMaxParticipants(?int $default_max_participants): self
    {
        $this->default_max_participants = $default_max_participants;
        return $this;
    }

    public function getTypicalDurationHours(): ?int
    {
        return $this->typical_duration_hours;
    }

    public function setTypicalDurationHours(?int $typical_duration_hours): self
    {
        $this->typical_duration_hours = $typical_duration_hours;
        return $this;
    }

    public function isRequiresPayment(): bool
    {
        return $this->requires_payment;
    }

    public function setRequiresPayment(bool $requires_payment): self
    {
        $this->requires_payment = $requires_payment;
        return $this;
    }

    public function isCertificateEnabled(): bool
    {
        return $this->certificate_enabled;
    }

    public function setCertificateEnabled(bool $certificate_enabled): self
    {
        $this->certificate_enabled = $certificate_enabled;
        return $this;
    }

    public function isRecordingEnabled(): bool
    {
        return $this->recording_enabled;
    }

    public function setRecordingEnabled(bool $recording_enabled): self
    {
        $this->recording_enabled = $recording_enabled;
        return $this;
    }

    public function getAllowedLocation(): string
    {
        return $this->allowed_location;
    }

    public function setAllowedLocation(string $allowed_location): self
    {
        $this->allowed_location = $allowed_location;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function setSortOrder(int $sort_order): self
    {
        $this->sort_order = $sort_order;
        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setEventType($this);
        }
        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            if ($event->getEventType() === $this) {
                $event->setEventType(null);
            }
        }
        return $this;
    }
}
