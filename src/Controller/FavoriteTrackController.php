<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FavoriteTrack;
use App\Entity\User;
use App\Service\FavoriteTrackService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/music/favorites')]
class FavoriteTrackController extends AbstractController
{
    /**
     * Lists all favorite tracks for the current user.
     *
     * @param FavoriteTrackService $service The favorite track service
     *
     * @return Response JSON response containing the user's favorite tracks
     */
    #[Route('/index', name: 'app_favorites_list', methods: ['GET'])]
    public function list(FavoriteTrackService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $favorites = $user->getFavoriteTracks();

        return $this->json($favorites, Response::HTTP_OK, [], ['groups' => 'favoriteTrack:read']);
    }

    /**
     * Adds a track to the user's favorites.
     *
     * @param string               $trackId The YouTube ID of the track
     * @param FavoriteTrackService $service The favorite track service
     *
     * @return Response JSON response indicating the track was added
     */
    #[Route('/add/{trackId}', name: 'app_favorites_add', methods: ['POST'])]
    public function add(string $trackId, FavoriteTrackService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $service->updateFavorite($user, $trackId);

        return $this->json(['status' => 'track added']);
    }

    /**
     * Removes a track from the user's favorites.
     *
     * @param int                  $trackId The YouTube ID of the track
     * @param FavoriteTrackService $service The favorite track service
     *
     * @return Response JSON response indicating the track was removed
     */
    #[Route('/remove/{trackId}', name: 'app_favorites_remove', methods: ['DELETE'])]
    public function remove(int $trackId, FavoriteTrackService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $service->updateFavorite($user, $trackId);

        return $this->json(['status' => 'track removed']);
    }

    /**
     * Gets the quantity of favorite tracks for the user.
     *
     * @return Response JSON response containing the quantity of favorite tracks
     */
    #[Route('/quantity', name: 'app_favorites_quantity', methods: ['GET'])]
    public function getQuantity(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $quantity = $user->getFavoriteTracksQuantity();

        return $this->json(['quantity' => $quantity]);
    }

    /**
     * Checks if a track is in the user's favorites.
     *
     * @param Request $request The HTTP request containing the track ID
     *
     * @return Response JSON response indicating if the track is a favorite
     */
    #[Route('/isFavorite', name: 'app_favorites_is_favorite', methods: ['POST'])]
    public function checkTrack(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $trackId = $request->query->get('videoId');

        /** @var array<FavoriteTrack> $favoriteTracksCollection */
        $favoriteTracksCollection = $user->getFavoriteTracks();
        $isFavorite = false;

        foreach ($favoriteTracksCollection as $track) {
            if ($track && $track->getTrack()->getYoutubeId() === $trackId) {
                $isFavorite = true;
                break;
            }
        }

        return $this->json(['status' => $isFavorite]);
    }
}
