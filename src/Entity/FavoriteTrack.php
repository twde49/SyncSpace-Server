<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FavoriteTrackRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FavoriteTrackRepository::class)]
class FavoriteTrack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['favoriteTrack:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'favoriteTracks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $relatedTo = null;

    #[ORM\ManyToOne(targetEntity: Track::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['favoriteTrack:read'])]
    private ?Track $track = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelatedTo(): ?User
    {
        return $this->relatedTo;
    }

    public function setRelatedTo(?User $relatedTo): static
    {
        $this->relatedTo = $relatedTo;

        return $this;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(Track $track): static
    {
        $this->track = $track;

        return $this;
    }
}
