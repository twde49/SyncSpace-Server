<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Service\PlaylistService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/music')]
class MusicController extends AbstractController
{
    #[Route('/playlists', name: 'app_music_playlist_show')]
    public function getPlaylists(PlaylistRepository $playlistRepository): Response
    {
        /** @var User $currentUser * */
        $currentUser = $this->getUser();
        $allPlaylists = $playlistRepository->findBy(['relatedTo' => $currentUser]);

        return $this->json($allPlaylists, 200, [], ['groups' => 'playlistSmallRead']);
    }

    #[Route('/playlist/create', name: 'app_music_playlist_create')]
    public function createPlaylist(Request $request, PlaylistService $service): Response
    {
        /** @var User $currentUser * */
        $currentUser = $this->getUser();
        $service->createPlaylist($currentUser->getId(), $request->get('folderName'));
    }
}
