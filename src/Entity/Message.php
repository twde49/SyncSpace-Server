<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('conversation:read')]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('conversation:read')]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    #[Groups('conversation:read')]
    private ?string $type = null;

    #[ORM\Column]
    #[Groups('conversation:read')]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column]
    #[Groups('conversation:read')]
    private ?bool $isRead = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('conversation:read')]
    private ?User $sender = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('conversation:read')]
    private ?string $attachment = null;

    #[ORM\Column]
    #[Groups('conversation:read')]
    private ?bool $isDeleted = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('conversation:read')]
    private ?string $fileSize = null;
    

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
        $this->isRead = false;
        $this->isDeleted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getFileSize(): ?string
    {
        return $this->fileSize;
    }

    public function setFileSize(?string $filesize): static
    {
        $this->fileSize = $filesize;

        return $this;
    }
}
