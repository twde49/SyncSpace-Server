<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[Vich\Uploadable]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['file:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['file:read'])]
    private ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['file:read'])]
    private ?string $filepath = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['file:read'])]
    private ?int $size = null;

    #[ORM\Column(length: 255)]
    #[Groups(['file:read'])]
    private ?string $mimeType = null;

    #[ORM\Column]
    #[Groups(['file:read'])]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['file:read'])]
    private ?User $owner = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'sharedFiles')]
    #[Groups(['file:read'])]
    private Collection $sharedWith;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['file:read'])]
    private ?string $originalName = null;

    #[Vich\UploadableField(mapping: 'files', fileNameProperty: 'filename')]
    private ?SymfonyFile $file = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['file:read'])]
    private ?bool $isFolder = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['remove'])]
    #[Groups(['file:read'])]
    private $children;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
        $this->sharedWith = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;
        $this->setFilepath('/uploads/files/'.$filename);

        return $this;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath): static
    {
        if (!$this->isFolder) {
            $this->filepath = $filepath;
        }

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        if (!$this->isFolder) {
            $this->size = $size;
        }

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getSharedWith(): Collection
    {
        return $this->sharedWith;
    }

    public function shareWith(User $sharedWith): static
    {
        if (!$this->sharedWith->contains($sharedWith)) {
            $this->sharedWith->add($sharedWith);
        }

        return $this;
    }

    public function revokeAccess(User $sharedWith): static
    {
        $this->sharedWith->removeElement($sharedWith);

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function setFile(?SymfonyFile $file = null): void
    {
        if (!$this->isFolder) {
            $this->file = $file;
            if ($file) {
                $this->uploadedAt = new \DateTimeImmutable();
            }
        }
    }

    public function getFile(): ?SymfonyFile
    {
        return $this->isFolder ? null : $this->file;
    }

    public function isFolder(): ?bool
    {
        return $this->isFolder;
    }

    public function setIsFolder(bool $isFolder): static
    {
        $this->isFolder = $isFolder;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(File $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
        }

        return $this;
    }

    public function removeChild(File $child): static
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
        }

        return $this;
    }
}
