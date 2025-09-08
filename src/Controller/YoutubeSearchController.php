<?php

declare(strict_types=1);

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
    /**
     * Searches YouTube for tracks based on a query.
     *
     * @param Request              $request              The HTTP request containing the search query
     * @param YoutubeService       $youtubeService       The YouTube service for searching videos
     * @param FavoriteTrackService $favoriteTrackService The favorite track service
     *
     * @return Response JSON response containing the search results
     */
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
