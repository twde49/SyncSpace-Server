<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $messages;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'users')]
    private Collection $conversations;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?string $lastName = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, PasswordItem>
     */
    #[ORM\OneToMany(targetEntity: PasswordItem::class, mappedBy: 'associatedTo', orphanRemoval: true)]
    private Collection $passwordItems;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $files;

    /**
     * @var Collection<int, File>
     */
    #[ORM\ManyToMany(targetEntity: File::class, mappedBy: 'sharedWith')]
    private Collection $sharedFiles;

    #[ORM\Column(length: 10000, nullable: true)]
    private ?string $masterPasswordHash = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'relatedTo', orphanRemoval: true)]
    private Collection $notifications;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'organizer', orphanRemoval: true)]
    private Collection $events;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $eventsAsParticipant;

    #[ORM\Column]
    #[Groups(['conversation:read', 'user:read', 'notification:read', 'event:read'])]
    private ?bool $isOnline = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->passwordItems = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->sharedFiles = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->eventsAsParticipant = new ArrayCollection();
        $this->isOnline = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setSender($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->addUser($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            $conversation->removeUser($this);
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setAuthor($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getAuthor() === $this) {
                $note->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PasswordItem>
     */
    public function getPasswordItems(): Collection
    {
        return $this->passwordItems;
    }

    public function addPasswordItem(PasswordItem $passwordItem): static
    {
        if (!$this->passwordItems->contains($passwordItem)) {
            $this->passwordItems->add($passwordItem);
            $passwordItem->setAssociatedTo($this);
        }

        return $this;
    }

    public function removePasswordItem(PasswordItem $passwordItem): static
    {
        if ($this->passwordItems->removeElement($passwordItem)) {
            // set the owning side to null (unless already changed)
            if ($passwordItem->getAssociatedTo() === $this) {
                $passwordItem->setAssociatedTo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setOwner($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getOwner() === $this) {
                $file->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getSharedFiles(): Collection
    {
        return $this->sharedFiles;
    }

    public function addSharedFile(File $sharedFile): static
    {
        if (!$this->sharedFiles->contains($sharedFile)) {
            $this->sharedFiles->add($sharedFile);
            $sharedFile->shareWith($this);
        }

        return $this;
    }

    public function removeSharedFile(File $sharedFile): static
    {
        if ($this->sharedFiles->removeElement($sharedFile)) {
            $sharedFile->revokeAccess($this);
        }

        return $this;
    }

    #[Groups(['user:read'])]
    public function isMasterPasswordSet(): bool
    {
        return !empty($this->masterPassword);
    }

    public function getMasterPasswordHash(): ?string
    {
        return $this->masterPasswordHash;
    }

    public function setMasterPasswordHash(string $password): self
    {
        $salt = 'syncspace_salt';
        $hash = hash_pbkdf2('sha256', $password, $salt, 100000, 64);
        $this->masterPasswordHash = $hash;

        return $this;
    }

    public function verifyMasterPassword(string $password): bool
    {
        $salt = 'syncspace_salt';

        return hash_pbkdf2('sha256', $password, $salt, 100000, 64) === $this->masterPasswordHash;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setRelatedTo($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getRelatedTo() === $this) {
                $notification->setRelatedTo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setOrganizer($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getOrganizer() === $this) {
                $event->setOrganizer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEventsAsParticipant(): Collection
    {
        return $this->eventsAsParticipant;
    }

    public function addEventsAsParticipant(Event $eventsAsParticipant): static
    {
        if (!$this->eventsAsParticipant->contains($eventsAsParticipant)) {
            $this->eventsAsParticipant->add($eventsAsParticipant);
            $eventsAsParticipant->addParticipant($this);
        }

        return $this;
    }

    public function removeEventsAsParticipant(Event $eventsAsParticipant): static
    {
        if ($this->eventsAsParticipant->removeElement($eventsAsParticipant)) {
            $eventsAsParticipant->removeParticipant($this);
        }

        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }
}
