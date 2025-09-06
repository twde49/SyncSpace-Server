<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TrackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TrackRepository::class)]
class Track
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['favoriteTrack:read', 'playlist:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['favoriteTrack:read', 'playlist:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['favoriteTrack:read', 'playlist:read'])]
    private ?string $artist = null;

    #[ORM\Column(length: 255)]
    #[Groups(['favoriteTrack:read', 'playlist:read'])]
    private ?string $youtubeId = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'currentTrack')]
    private Collection $usersListening;

    #[ORM\Column(length: 255)]
    #[Groups(['favoriteTrack:read', 'playlist:read'])]
    private ?string $coverUrl = null;

    public function __construct()
    {
        $this->usersListening = new ArrayCollection();
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

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getYoutubeId(): ?string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(string $youtubeId): static
    {
        $this->youtubeId = $youtubeId;

        return $this;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(string $coverUrl): static
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }
}
