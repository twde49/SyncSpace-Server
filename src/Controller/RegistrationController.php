<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSettings;
use App\Form\RegistrationFormType;
use App\Security\SecurityAuthenticator;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $security->login($user, SecurityAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/api/register', methods: 'POST')]
    public function registerApi(SerializerInterface $serializer, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $manager, MailerInterface $mailer, MailerService $mailerService): Response
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $parameters = json_decode($request->getContent(), true);

        $userSettings = new UserSettings();

        $user->setUserSettings($userSettings);
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $parameters['password']
            )
        );
        $verificationCode = $this->generateVerificationCode();
        $user->setVerificationCode($verificationCode);
        $user->setVerificationCodeValidUntil(new \DateTimeImmutable('+10 minutes', new \DateTimeZone('UTC')));
        $user->setIsValidated(false);

        $manager->persist($user);
        $manager->flush();

        $email = $mailerService->sendVerificationMail($user);

        try {
            $mailer->send($email);

            return $this->json([
                'message' => 'Votre compte a été créé. Un code de vérification a été envoyé à votre adresse e-mail.',
                'userId' => $user->getId(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Votre compte a été créé, mais l\'envoi de l\'e-mail de vérification a échoué. Veuillez contacter le support.',
                'error' => $e->getMessage(),
                'userId' => $user->getId(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/sendVerificationCode/{id}', methods: ['POST'])]
    public function sendVerificationCode(User $user, EntityManagerInterface $entityManager, MailerService $mailerService, MailerInterface $mailer): Response
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->getVerificationCodeValidUntil() > new \DateTimeImmutable()) {
            return $this->json([
                'message' => 'Un code de vérification a déjà été envoyé récemment.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $verificationCode = $this->generateVerificationCode();
        $user->setVerificationCode($verificationCode);
        $user->setVerificationCodeValidUntil(new \DateTimeImmutable('+10 minutes', new \DateTimeZone('UTC')));
        $user->setIsValidated(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $email = $mailerService->sendVerificationMail($user);

        try {
            $mailer->send($email);

            return $this->json([
                'message' => 'Un nouveau code de vérification a été envoyé à votre adresse e-mail.',
                'userId' => $user->getId(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'L\'envoi du nouveau code de vérification a échoué. Veuillez contacter le support.',
                'error' => $e->getMessage(),
                'userId' => $user->getId(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/verifyVerificationCode', methods: ['POST'])]
    public function verifyVerificationCode(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parameters = json_decode($request->getContent(), true);

        $userId = $parameters['userId'] ?? null;
        $receivedCode = $parameters['code'] ?? null;

        if (null === $userId || null === $receivedCode) {
            return $this->json([
                'message' => 'Les paramètres userId et verificationCode sont requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->isValidated()) {
            return $this->json([
                'message' => 'Votre compte est déjà validé.',
            ], Response::HTTP_OK);
        }

        if ($user->getVerificationCodeValidUntil() < new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
            return $this->json([
                'message' => 'Code de vérification expiré.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getVerificationCode() !== $receivedCode) {
            return $this->json([
                'message' => 'Code de vérification invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setIsValidated(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Code vérifié avec succès. Votre compte est maintenant validé.',
            'userId' => $user->getId(),
        ], Response::HTTP_OK);
    }

    private function generateVerificationCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
