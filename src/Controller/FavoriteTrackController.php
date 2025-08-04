<?php

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
    #[Route('', name: 'app_favorites_list', methods: ['GET'])]
    public function list(FavoriteTrackService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $favorites = $service->getFavoritesByUser($user);

        return $this->json($favorites?->getTracks() ?? []);
    }

    #[Route('/add/{trackId}', name: 'app_favorites_add', methods: ['POST'])]
    public function add(string $trackId, FavoriteTrackService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $service->addTrackToFavorites($user, $trackId);

        return $this->json(['status' => 'track added']);
    }

    #[Route('/remove/{trackId}', name: 'app_favorites_remove', methods: ['DELETE'])]
    public function remove(int $trackId, FavoriteTrackService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $service->removeTrackFromFavorites($user, $trackId);

        return $this->json(['status' => 'track removed']);
    }

    #[Route('/quantity', name: 'app_favorites_quantity', methods: ['GET'])]
    public function getQuantity(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $quantity = $user->getFavoriteTracksQuantity();

        return $this->json(['quantity' => $quantity]);
    }
    
    #[Route('/isFavorite', name: 'app_favorites_is_favorite', methods: ['POST'])]
    public function checkTrack(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $trackId = $request->query->get('videoId');
        
        /** @var array<FavoriteTrack> $favoriteTracksCollection */
        $favoriteTracksCollection = $user->getFavoriteTracks();
        $isFavorite = false;

        foreach ($favoriteTracksCollection->getTracks() as $track) {
            if ($track && $track->getYoutubeId() === $trackId) {
                $isFavorite = true;
                break;
            }
        }

        return $this->json(['status' => $isFavorite]);
    }
}