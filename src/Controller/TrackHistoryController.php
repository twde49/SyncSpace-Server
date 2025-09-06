<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\TrackHistoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/music/history')]
class TrackHistoryController extends AbstractController
{
    #[Route('', name: 'app_track_history_list', methods: ['GET'])]
    public function list(TrackHistoryService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $history = $service->getUserHistory($user);

        return $this->json($history);
    }

    #[Route('/add', name: 'history_add_track', methods: ['POST'])]
    public function addTrack(Request $request, TrackHistoryService $trackHistoryService): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['trackId'])) {
            return $this->json(['error' => 'trackId is required'], 400);
        }

        $trackHistoryService->addTrackToHistory($currentUser->getId(), $data['trackId']);

        return $this->json(['status' => 'Track added to history']);
    }
}
