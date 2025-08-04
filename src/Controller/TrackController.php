<?php

namespace App\Controller;

use App\Entity\FavoriteTrack;
use App\Entity\Track;
use App\Entity\User;
use App\Repository\TrackRepository;
use App\Service\TrackService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route("api/track")]
final class TrackController extends AbstractController
{
    #[Route("/search", name: "app_track_search", methods: ["GET"])]
    public function search(Request $request, TrackRepository $repo): Response
    {
        $query = $request->query->get("query");

        return $this->json($repo->searchByKeyword($query));
    }

    #[Route("/suggest", name: "app_track_suggest", methods: ["GET"])]
    public function suggest(Request $request, TrackRepository $repo): Response
    {
        $genre = $request->query->get("genre");

        return $this->json($repo->suggestByGenre($genre));
    }

    #[Route("/add", name: "add_track", methods: ["POST"])]
    public function addTrack(
        Request $request,
        TrackService $trackService
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (empty($data["youtubeId"]) || empty($data["title"])) {
            return $this->json(
                ["error" => "youtubeId and title are required"],
                400
            );
        }

        $track = $trackService->createOrGetTrack(
            $data["youtubeId"],
            $data["title"],
            $data["artist"] ?? "",
            $data["genre"] ?? ""
        );

        return $this->json([
            "id" => $track->getId(),
            "title" => $track->getTitle(),
            "youtubeId" => $track->getYoutubeId(),
            "artist" => $track->getArtist(),
            "genre" => $track->getCoverUrl(),
        ]);
    }

    #[
        Route(
            "/setCurrentTrack",
            name: "set_current_user_track",
            methods: ["POST"]
        )
    ]
    public function setCurrentTrackForUser(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
    }

    #[Route("/like", name: "like_track", methods: ["POST"])]
    public function likeTrack(
        Request $request,
        EntityManagerInterface $manager
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (
            empty($data["track"]["title"]) ||
            empty($data["track"]["youtubeId"])
        ) {
            return $this->json(
                ["error" => 'The fields "title" and "youtubeId" are required.'],
                400
            );
        }

        $trackData = $data["track"];

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $trackRepository = $manager->getRepository(Track::class);
        $track = $trackRepository->findOneBy([
            "youtubeId" => $trackData["youtubeId"],
        ]);

        if (!$track) {
            $track = new Track();
            $track->setTitle($trackData["title"]);
            $track->setArtist($trackData["artist"] ?? null);
            $track->setYoutubeId($trackData["youtubeId"]);
            $track->setCoverUrl($trackData["coverUrl"] ?? null);

            $manager->persist($track);
        }

        foreach ($currentUser->getFavoriteTracks() as $favoriteTrack) {
            if ($favoriteTrack->getTracks()->contains($track)) {
                return $this->json(
                    [["message" => "Track is already in your favorites"]],
                    400
                );
            }
        }

        $favoriteTrack = new FavoriteTrack();
        $favoriteTrack->setRelatedTo($currentUser);
        $favoriteTrack->addTrack($track);

        $manager->persist($favoriteTrack);
        $manager->flush();

        return $this->json([["message" => "Track liked successfully"]]);
    }
}
