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
