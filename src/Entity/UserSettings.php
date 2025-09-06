<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserSettingsRepository::class)]
class UserSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userSettings', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $relatedTo = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private array $modulesLayout = [];

    #[ORM\Column(length: 30)]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?string $theme = null;

    #[ORM\Column]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?bool $notificationsEnabled = null;

    #[ORM\Column]
    private ?bool $geolocationEnabled = null;

    public function __construct()
    {
        $this->modulesLayout = [];
        $this->theme = 'dark';
        $this->notificationsEnabled = true;
        $this->geolocationEnabled = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelatedTo(): ?User
    {
        return $this->relatedTo;
    }

    public function setRelatedTo(User $relatedTo): static
    {
        $this->relatedTo = $relatedTo;

        return $this;
    }

    public function getModulesLayout(): array
    {
        return $this->modulesLayout;
    }

    public function setModulesLayout(array $modulesLayout): static
    {
        $this->modulesLayout = $modulesLayout;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function isNotificationsEnabled(): ?bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $notificationsEnabled): static
    {
        $this->notificationsEnabled = $notificationsEnabled;

        return $this;
    }

    public function isGeolocationEnabled(): ?bool
    {
        return $this->geolocationEnabled;
    }

    public function setGeolocationEnabled(bool $geolocationEnabled): static
    {
        $this->geolocationEnabled = $geolocationEnabled;

        return $this;
    }
}
