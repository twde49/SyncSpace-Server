<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Track;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/settings')]
class UserSettingsController extends AbstractController
{
    /**
     * Enables or disables notifications for the current user.
     *
     * @param Request                $request       The HTTP request containing notification settings
     * @param EntityManagerInterface $entityManager The entity manager
     *
     * @return Response JSON response indicating the result
     */
    #[
        Route(
            '/enable-disable-notifications',
            name: 'enable_disable_notifications',
            methods: ['POST']
        )
    ]
    public function enableDisableNotifications(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $requestData = $request->toArray();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentUser
            ->getUserSettings()
            ->setNotificationsEnabled($requestData['notifications_enabled']);

        $entityManager->persist($currentUser);
        $entityManager->flush();

        return $this->json([
            'message' => 'Notifications setting updated successfully',
        ]);
    }

    /**
     * Enables or disables geolocation for the current user.
     *
     * @param Request                $request       The HTTP request containing geolocation settings
     * @param EntityManagerInterface $entityManager The entity manager
     *
     * @return Response JSON response indicating the result
     */
    #[
        Route(
            '/enable-disable-geolocation',
            name: 'enable_disable_geolocation',
            methods: ['POST']
        )
    ]
    public function enableDisableGeolocation(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $requestData = $request->toArray();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentUser
            ->getUserSettings()
            ->setGeolocationEnabled($requestData['geolocation_enabled']);

        $entityManager->persist($currentUser);
        $entityManager->flush();

        return $this->json([
            'message' => 'Geolocation setting updated successfully',
        ]);
    }

    /**
     * Updates the theme preference for the current user.
     *
     * @param Request                $request       The HTTP request containing theme preference
     * @param EntityManagerInterface $entityManager The entity manager
     *
     * @return Response JSON response indicating the result
     */
    #[
        Route(
            '/update-theme-preference',
            name: 'update_theme_preference',
            methods: ['POST']
        )
    ]
    public function updateThemePreference(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $requestData = $request->toArray();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentUser->getUserSettings()->setTheme($requestData['theme']);

        $entityManager->persist($currentUser);
        $entityManager->flush();

        return $this->json([
            'message' => 'Theme preference updated successfully',
        ]);
    }

    /**
     * Updates the current track for the user.
     *
     * @param Request                $request The HTTP request containing track data
     * @param EntityManagerInterface $manager The entity manager
     *
     * @return Response JSON response indicating the result
     */
    #[
        Route(
            '/update-current-track',
            name: 'update_current_track',
            methods: ['POST']
        )
    ]
    public function updateCurrentTrack(Request $request, EntityManagerInterface $manager): Response
    {
        $data = $request->toArray();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $newTrack = new Track();
        $newTrack->setYoutubeId($data['youtubeId']);
        $newTrack->setTitle($data['title']);
        $newTrack->setArtist($data['artist'] ?? '');
        $newTrack->setCoverUrl($data['coverUrl'] ?? '');

        $currentUser->setCurrentTrack($newTrack);
        $manager->persist($newTrack);
        $manager->persist($currentUser);
        $manager->flush();

        return $this->json([
            'message' => 'Current track updated successfully',
        ]);
    }

    /**
     * Gets the current track for the user.
     *
     * @return Response JSON response containing the current track or error message
     */
    #[
        Route(
            '/current-track',
            name: 'get_current_track',
            methods: ['GET']
        )
    ]
    public function getCurrentUserTrack(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentTrack = $currentUser->getCurrentTrack();

        if (null === $currentTrack) {
            return $this->json([
                'message' => 'No current track set.',
                'track' => null,
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'youtubeId' => $currentTrack->getYoutubeId(),
            'title' => $currentTrack->getTitle(),
            'artist' => $currentTrack->getArtist(),
            'coverUrl' => $currentTrack->getCoverUrl(),
        ]);
    }
}
