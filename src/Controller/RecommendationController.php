<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RecommendationController extends AbstractController
{
    public function __construct(private readonly RecommendationService $recommendationService)
    {
    }

    #[Route('/api/recommendations', name: 'api_recommendations', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $artist = $request->query->get('artist');
        $track = $request->query->get('track');
        $offset = (int) $request->query->get('offset', 0);

        if (!$artist) {
            return $this->json(['error' => 'Missing artist parameter'], 400);
        }

        $recommendations = $this->recommendationService->getRecommendations($artist, $track, 10);
        $recommendation = $this->recommendationService->hydrateYoutubeIdAtOffset($recommendations, $offset);

        if (!$recommendation) {
            $recommendations = $this->recommendationService->getRecommendations($artist, null, 10);
            $recommendation = $this->recommendationService->hydrateYoutubeIdAtOffset($recommendations, $offset);
        }

        if (!$recommendation) {
            $recommendation = $this->recommendationService->getDiscoverRecommendation();
        }

        if (!$recommendation) {
            return $this->json(['error' => 'No recommendation found'], 404);
        }

        return $this->json($recommendation);
    }

    #[Route('/api/discover', name: 'api_discover', methods: ['GET'])]
    public function discover(Request $request): JsonResponse
    {
        $recommendation = $this->recommendationService->getDiscoverRecommendation();

        if (!$recommendation) {
            return $this->json(['error' => 'No discovery track found'], 404);
        }

        return $this->json($recommendation);
    }
}
