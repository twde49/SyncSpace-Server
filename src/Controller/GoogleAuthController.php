<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserSettings;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/api/auth/google', name: 'app_google_login')]
    public function login(): RedirectResponse
    {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
        $scopes =
            'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';

        $url =
            'https://accounts.google.com/o/oauth2/v2/auth?'.
            http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $scopes,
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);

        return new RedirectResponse($url);
    }

    #[Route('/api/auth/google/callback', name: 'app_google_callback')]
    public function callback(
        Request $request,
        UserRepository $userRepository,
    ): RedirectResponse {
        $code = $request->query->get('code');

        if (!$code) {
            return new RedirectResponse(
                'http://localhost:4000/login?error=auth_failed',
            );
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://oauth2.googleapis.com/token',
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'code' => $code,
                        'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
                        'grant_type' => 'authorization_code',
                    ],
                ],
            );

            $accessTokenData = $response->toArray();
            $accessToken = $accessTokenData['access_token'] ?? null;

            if (!$accessToken) {
                return new RedirectResponse(
                    'http://localhost:4000/login?error=token_failed',
                );
            }

            $userInfoResponse = $this->httpClient->request(
                'GET',
                'https://www.googleapis.com/oauth2/v3/userinfo',
                [
                    'headers' => ['Authorization' => "Bearer $accessToken"],
                ],
            );

            $googleUser = $userInfoResponse->toArray();
            $googleId = $googleUser['sub'];
            $email = $googleUser['email'];
            $firstName = $googleUser['given_name'] ?? null;
            $lastName = $googleUser['family_name'] ?? null;

            $user = $userRepository->findOneBy(['googleId' => $googleId]);

            if (!$user) {
                $user = $userRepository->findOneBy(['email' => $email]);

                if ($user) {
                    $user->setGoogleId($googleId);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                } else {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleId);
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);
                    $user->setIsValidated(true);
                    $randomPassword = bin2hex(random_bytes(16));
                    $user->setPassword(
                        password_hash($randomPassword, PASSWORD_BCRYPT),
                    );
                    $userSettings = new UserSettings();
                    $user->setUserSettings($userSettings);

                    $this->entityManager->persist($userSettings);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }
            }

            $jwtToken = $this->jwtManager->create($user);

            $userData = [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'userEmail' => $user->getEmail(),
                'masterPasswordSet' => $user->isMasterPasswordSet(),
                'parameters' => [
                    'theme' => $user->getUserSettings()->getTheme(),
                    'modulesLayout' => $user
                        ->getUserSettings()
                        ->getModulesLayout(),
                    'notificationsEnabled' => $user
                        ->getUserSettings()
                        ->isNotificationsEnabled(),
                    'geolocationEnabled' => $user
                        ->getUserSettings()
                        ->isGeolocationEnabled(),
                ],
                'currentTrack' => $user->getCurrentTrack(),
            ];

            $encodedUserData = base64_encode(json_encode($userData));

            return new RedirectResponse(
                "http://localhost:4000/login?token={$jwtToken}&user={$encodedUserData}",
            );
        } catch (\Exception $e) {
            error_log('Google Auth Callback Error: '.$e->getMessage());

            return new RedirectResponse(
                'http://localhost:4000/login?error=server_error',
            );
        }
    }
}
