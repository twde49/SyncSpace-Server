<?php

namespace App\Controller;

use App\Service\PlaylistService;
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
}

