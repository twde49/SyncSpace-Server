<?php

namespace App\Controller;

use App\Entity\PasswordItem;
use App\Repository\PasswordItemRepository;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function addPassword(Request $request, EncryptionService $encryptionService, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $passwordItem = new PasswordItem();
        $passwordItem->setAssociatedTo($user);
        $passwordItem->setUrl($data['url']);
        $passwordItem->setName($data['name']);
        $passwordItem->setEmail($data['email']);

        // 🔒 Chiffrement sécurisé
        $encrypted = $encryptionService->encryptData($data['password']);
        $passwordItem->setPasswordEncrypted($encrypted['ciphertext'], $encrypted['iv']);

        $em->persist($passwordItem);
        $em->flush();

        return new JsonResponse(['message' => 'Mot de passe ajouté avec succès'], 201);
    }

    #[Route('/list', methods: ['GET'])]
    public function listPasswords(PasswordItemRepository $passwordRepo, EncryptionService $encryptionService): JsonResponse
    {
        $user = $this->getUser();
        $passwords = $passwordRepo->findBy(['associatedTo' => $user]);

        $response = [];
        foreach ($passwords as $passwordItem) {
            $response[] = [
                'id' => $passwordItem->getId(),
                'name' => $passwordItem->getName(),
                'email' => $passwordItem->getEmail(),
                'password' => $encryptionService->decryptData($passwordItem->getPasswordEncrypted(), $passwordItem->getIv())
            ];
        }

        return new JsonResponse($response);
    }
}
