<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["event:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["event:read"])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["event:read"])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(["event:read"])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    #[Groups(["event:read"])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    #[Groups(["event:read"])]
    private ?bool $isAllDay = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["event:read"])]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: "events")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["event:read"])]
    private ?User $organizer = null;

    /**
     * @var Collection<int, User>
     */
    #[
        ORM\ManyToMany(
            targetEntity: User::class,
            inversedBy: "eventsAsParticipant"
        )
    ]
    #[Groups(["event:read"])]
    private Collection $participants;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["event:read"])]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["event:read"])]
    private ?string $color = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function isAllDay(): ?bool
    {
        return $this->isAllDay;
    }

    public function setAllDay(bool $isAllDay): static
    {
        $this->isAllDay = $isAllDay;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(User $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }
}
