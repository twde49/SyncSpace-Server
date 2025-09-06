<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\User;
use App\Service\PlaylistService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/music/playlist')]
class PlaylistController extends AbstractController
{
    #[Route('/add-track', name: 'playlist_add_track', methods: ['POST'])]
    public function addTrack(Request $request, PlaylistService $playlistService): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['playlistId']) || empty($data['trackId'])) {
            return $this->json(['error' => 'playlistId and trackId are required'], 400);
        }

        $playlistService->addTrackToPlaylist($data['playlistId'], $data['trackId']);

        return $this->json(['status' => 'Track added to playlist']);
    }

    #[Route('/index', name: 'playlist_index', methods: ['GET'])]
    public function getPlaylists(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($user->getPlaylists(), Response::HTTP_OK, [], ['groups' => 'playlist:read']);
    }

    #[Route('/new', name: 'playlist_create', methods: ['POST'])]
    public function createPlaylist(Request $request, PlaylistService $playlistService): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'name is required'], 400);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $playlistService->createPlaylist($currentUser, $data['name']);

        return $this->json(['status' => 'Playlist created']);
    }

    #[Route('/quantity', name: 'playlist_quantity', methods: ['GET'])]
    public function getPlaylistQuantity(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['quantity' => count($user->getPlaylists())], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'playlist_get_tracks', methods: ['GET'])]
    public function getPlaylistTracks(?Playlist $playlist): Response
    {
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($playlist->getRelatedTo() !== $user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($playlist->getTracks(), Response::HTTP_OK, [], ['groups' => 'playlist:read']);
    }

    #[Route('/remove/{id}', name: 'playlist_remove', methods: ['DELETE'])]
    public function removePlaylist(?Playlist $playlist, EntityManagerInterface $entityManager): Response
    {
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($playlist->getRelatedTo() !== $user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($playlist);
        $entityManager->flush();

        return $this->json(['status' => 'Playlist removed']);
    }

    #[Route('/update/{id}', name: 'playlist_update', methods: ['PUT'])]
    public function updatePlaylist(?Playlist $playlist, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$playlist) {
            return $this->json(['error' => 'Playlist not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($playlist->getRelatedTo() !== $user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'name is required'], 400);
        }

        $playlist->setName($data['name']);
        $entityManager->flush();

        return $this->json(['status' => 'Playlist updated successfully']);
    }
}
