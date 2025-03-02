<?php

namespace App\Entity;

use App\Repository\PasswordItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Represents a password management item associated with a user.
 *
 * This entity is used to store encrypted passwords along with related metadata such as
 * URLs, usernames, email addresses, and notes. Password encryption is achieved using
 * the AES-GCM encryption algorithm.
 *
 * Fields:
 * - id: Unique identifier for the password item.
 * - url: The associated URL or website for the stored credentials.
 * - name: The name or description of the credential.
 * - email: The email address associated with the account.
 * - passwordEncrypted: The encrypted password.
 * - notesEncrypted: Optional encrypted notes related to the password.
 * - iv: Initialization vector (IV) used for the AES-GCM encryption.
 * - isFavorite: Boolean flag indicating if the password item is marked as a favorite.
 * - associatedTo: Reference to the user associated with this password item.
 * - mustBeUpdated: Boolean flag indicating if the password requires an update.
 *
 * Encryption and Decryption:
 * - Passwords are encrypted using `openssl_encrypt` with AES-GCM encryption, utilizing
 *   a random IV for enhanced security.
 * - Decryption relies on the AES-GCM algorithm with the same IV and encryption key.
 *
 * Exception Handling:
 * - Throws RandomException if an error occurs during the generation of random bytes
 *   for the IV in the `setPasswordEncrypted()` method.
 */
#[ORM\Entity(repositoryClass: PasswordItemRepository::class)]
class PasswordItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $url = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $passwordEncrypted = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notesEncrypted = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $iv = null; // IV pour AES-GCM

    #[ORM\Column]
    private ?bool $isFavorite = false;

    #[ORM\ManyToOne(inversedBy: 'passwordItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $associatedTo = null;

    #[ORM\Column]
    private ?bool $mustBeUpdated = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
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

    public function getNotesEncrypted(): ?string
    {
        return $this->notesEncrypted;
    }

    public function setNotesEncrypted(?string $notesEncrypted): static
    {
        $this->notesEncrypted = $notesEncrypted;

        return $this;
    }

    public function getIv(): ?string
    {
        return $this->iv;
    }

    public function setIv(string $iv): static
    {
        $this->iv = $iv;

        return $this;
    }

    public function isFavorite(): ?bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }

    public function getAssociatedTo(): ?User
    {
        return $this->associatedTo;
    }

    public function setAssociatedTo(?User $associatedTo): static
    {
        $this->associatedTo = $associatedTo;

        return $this;
    }

    public function mustBeUpdated(): ?bool
    {
        return $this->mustBeUpdated;
    }

    public function setMustBeUpdated(bool $mustBeUpdated): static
    {
        $this->mustBeUpdated = $mustBeUpdated;

        return $this;
    }

    public function getPasswordEncrypted(): ?string
    {
        return $this->passwordEncrypted;
    }

    /**
     * @throws RandomException
     */
    public function setPasswordEncrypted(string $password, string $encryptionKey): static
    {
        $iv = random_bytes(12); // Génère un IV aléatoire de 12 octets
        $ciphertext = openssl_encrypt($password, "aes-256-gcm", $encryptionKey, 0, $iv, $tag);
        $this->passwordEncrypted = base64_encode($ciphertext . "::" . $tag);
        $this->iv = base64_encode($iv);

        return $this;
    }

    public function decryptPassword(string $encryptionKey): ?string
    {
        $data = explode("::", base64_decode($this->passwordEncrypted));
        $iv = base64_decode($this->iv);
        return openssl_decrypt($data[0], "aes-256-gcm", $encryptionKey, 0, $iv, $data[1]);
    }
}
