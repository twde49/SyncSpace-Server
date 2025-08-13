<?php

namespace App\Controller;

use App\Service\FavoriteTrackService;
use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/music/youtube')]
class YoutubeSearchController extends AbstractController
{
    #[Route('/search', name: 'app_youtube_search', methods: ['GET'])]
    public function search(Request $request, YoutubeService $youtubeService, FavoriteTrackService $favoriteTrackService): Response
    {
        $query = $request->query->get('query');

        if (!$query) {
            return $this->json(['error' => 'Missing query'], 400);
        }

        $results = $youtubeService->search($query);

        return $this->json($results);
    }
}
