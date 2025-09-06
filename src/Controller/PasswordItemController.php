<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PasswordItem;
use App\Entity\User;
use App\Repository\PasswordItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller responsible for managing passwords through an API.
 * Provides functionality to add and list user-associated passwords.
 *
 * Methods:
 * - addPassword: Handles the creation and secure storage of a new password entry.
 * - listPasswords: Retrieves and decrypts the list of passwords associated with the authenticated user.
 *
 * Annotations:
 * - `#[Route('/api/passwords')]` defines the base route for the controller.
 */
#[Route('/api/passwords')]
class PasswordItemController extends AbstractController
{
    /**
     * @throws RandomException
     */
    #[Route('/add', methods: ['POST'])]
    public function addPassword(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $passwordItem = new PasswordItem();
        $passwordItem->setAssociatedTo($user);
        $passwordItem->setUrl($data['url']);
        $passwordItem->setName($data['name'] ?? null);
        $passwordItem->setEmail($data['email'] ?? null);
        $passwordItem->setUpdatedAt(new \DateTimeImmutable());

        $passwordItem->setPasswordEncrypted($data['passwordEncrypted'], $data['iv']);

        $passwordItem->setNotes($data['notes']);

        $passwordItem->setIsFavorite((bool) ($data['isFavorite'] ?? false));

        $em->persist($passwordItem);
        $em->flush();

        return $this->json(['message' => 'Mot de passe ajouté avec succès'], 201);
    }

    #[Route('/list', methods: ['GET'])]
    public function listPasswords(PasswordItemRepository $passwordRepo): Response
    {
        $user = $this->getUser();
        $passwords = $passwordRepo->findBy(['associatedTo' => $user]);

        $response = [];
        foreach ($passwords as $passwordItem) {
            $response[] = [
                'id' => $passwordItem->getId(),
                'url' => $passwordItem->getUrl(),
                'name' => $passwordItem->getName(),
                'email' => $passwordItem->getEmail(),
                'passwordEncrypted' => $passwordItem->getPasswordEncrypted(),
                'notes' => $passwordItem->getNotes(),
                'iv' => $passwordItem->getIv(),
                'isFavorite' => $passwordItem->isFavorite(),
                'updatedAt' => $passwordItem->getUpdatedAt(),
                'mustBeUpdated' => $passwordItem->mustBeUpdated(),
            ];
        }

        return $this->json($response);
    }

    #[Route('/set-master-password', methods: ['POST'])]
    public function setMasterPassword(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getMasterPasswordHash()) {
            return $this->json(['error' => 'Master password is already set'], 400);
        }

        $user->setMasterPasswordHash($data['masterPassword']);
        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Master password set successfully'], 201);
    }

    #[Route('/get-master-password-hash', methods: ['GET'])]
    public function getMasterPasswordHash(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'hash' => $user->getMasterPasswordHash() ?? null,
        ]);
    }

    #[Route('/count', methods: ['GET'])]
    public function howManyPasswords(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        foreach ($user->getPasswordItems() as $item) {
            if ($item->getUpdatedAt() < new \DateTime('-90 days')) {
                $item->setMustBeUpdated(true);
            } else {
                $item->setMustBeUpdated(false);
            }
            $entityManager->persist($item);
        }
        $mustBeUpdatedCount = $user->getPasswordItems()->filter(fn ($item) => $item->mustBeUpdated())->count();

        $entityManager->flush();

        return $this->json([
            'passwordCount' => $user->getPasswordItems()->count(),
            'compromisedPasswordCount' => $mustBeUpdatedCount,
        ]);
    }

    #[Route('/update/{id}', methods: ['PATCH'])]
    public function updatePasswordItem(?PasswordItem $passwordItem, Request $request, EntityManagerInterface $em): Response
    {
        if (!$passwordItem) {
            return $this->json(['error' => 'Password item not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Ensure the password item belongs to the authenticated user
        if ($passwordItem->getAssociatedTo() !== $user) {
            return $this->json(['error' => 'Unauthorized access to this password item'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['url'])) {
            $passwordItem->setUrl($data['url']);
        }
        if (isset($data['name'])) {
            $passwordItem->setName($data['name']);
        }
        if (isset($data['email'])) {
            $passwordItem->setEmail($data['email']);
        }
        if (isset($data['passwordEncrypted']) && isset($data['iv'])) {
            $passwordItem->setPasswordEncrypted($data['passwordEncrypted'], $data['iv']);
        } elseif (isset($data['passwordEncrypted']) && !isset($data['iv'])) {
            return $this->json(['error' => 'IV is required when updating passwordEncrypted'], 400);
        }
        if (isset($data['notes'])) {
            $passwordItem->setNotes($data['notes']);
        }
        if (isset($data['isFavorite'])) {
            $passwordItem->setIsFavorite((bool) $data['isFavorite']);
        }

        $passwordItem->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($passwordItem);
        $em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour avec succès']);
    }

    #[Route('/remove/{id}', methods: ['DELETE'])]
    public function removePasswordItem(?PasswordItem $passwordItem, EntityManagerInterface $entityManager): Response
    {
        if (!$passwordItem) {
            return $this->json(['error' => 'Password item not found'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        $user->removePasswordItem($passwordItem);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json('Mot de passe supprimé avec succès');
    }

    #[Route('/toggle-favorite/{id}', methods: ['PATCH'])]
    public function toggleFavoritePasswordItem(?PasswordItem $passwordItem, EntityManagerInterface $entityManager): Response
    {
        if (!$passwordItem) {
            return $this->json(['error' => 'Password item not found'], 404);
        }

        $passwordItem->setIsFavorite(!$passwordItem->isFavorite());
        $entityManager->persist($passwordItem);
        $entityManager->flush();

        return $this->json('Mot de passe supprimé avec succès');
    }
}
