<?php

declare(strict_types=1);

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

#[Route('api/track')]
final class TrackController extends AbstractController
{
    /**
     * Searches for tracks by a keyword.
     *
     * @param Request         $request The HTTP request containing the search query
     * @param TrackRepository $repo    The track repository
     *
     * @return Response JSON response containing the search results
     */
    #[Route('/search', name: 'app_track_search', methods: ['GET'])]
    public function search(Request $request, TrackRepository $repo): Response
    {
        $query = $request->query->get('query');

        return $this->json($repo->searchByKeyword($query));
    }

    /**
     * Suggests tracks based on genre.
     *
     * @param Request         $request The HTTP request containing the genre
     * @param TrackRepository $repo    The track repository
     *
     * @return Response JSON response containing suggested tracks
     */
    #[Route('/suggest', name: 'app_track_suggest', methods: ['GET'])]
    public function suggest(Request $request, TrackRepository $repo): Response
    {
        $genre = $request->query->get('genre');

        return $this->json($repo->suggestByGenre($genre));
    }

    /**
     * Adds a new track.
     *
     * @param Request      $request      The HTTP request containing track data
     * @param TrackService $trackService The track service
     *
     * @return Response JSON response containing the added track details
     */
    #[Route('/add', name: 'add_track', methods: ['POST'])]
    public function addTrack(
        Request $request,
        TrackService $trackService,
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (empty($data['youtubeId']) || empty($data['title'])) {
            return $this->json(
                ['error' => 'youtubeId and title are required'],
                400
            );
        }

        $track = $trackService->createOrGetTrack(
            $data['youtubeId'],
            $data['title'],
            $data['artist'] ?? '',
            $data['genre'] ?? ''
        );

        return $this->json([
            'id' => $track->getId(),
            'title' => $track->getTitle(),
            'youtubeId' => $track->getYoutubeId(),
            'artist' => $track->getArtist(),
            'genre' => $track->getCoverUrl(),
        ]);
    }

    /**
     * Sets the current track for the user.
     *
     * @param Request $request The HTTP request containing track data
     *
     * @return Response JSON response indicating the current track was set
     */
    #[
        Route(
            '/setCurrentTrack',
            name: 'set_current_user_track',
            methods: ['POST']
        )
    ]
    public function setCurrentTrackForUser(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
    }

    /**
     * Likes or removes a track from user's favorites.
     *
     * @param Request                $request The HTTP request containing track data
     * @param EntityManagerInterface $manager The entity manager
     *
     * @return Response JSON response indicating the result of the like/remove action
     */
    #[Route('/like', name: 'like_track', methods: ['POST'])]
    public function likeTrack(
        Request $request,
        EntityManagerInterface $manager,
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (
            empty($data['track']['title'])
            || empty($data['track']['youtubeId'])
        ) {
            return $this->json(
                ['error' => 'The fields "title" and "youtubeId" are required.'],
                400
            );
        }

        $trackData = $data['track'];

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $trackRepository = $manager->getRepository(Track::class);
        $track = $trackRepository->findOneBy([
            'youtubeId' => $trackData['youtubeId'],
        ]);

        if (!$track) {
            $track = new Track();
            $track->setTitle($trackData['title']);
            $track->setArtist($trackData['artist'] ?? null);
            $track->setYoutubeId($trackData['youtubeId']);
            $track->setCoverUrl($trackData['coverUrl'] ?? null);

            $manager->persist($track);
        }

        foreach ($currentUser->getFavoriteTracks() as $favoriteTrack) {
            if ($favoriteTrack->getTrack() && $favoriteTrack->getTrack()->getId() === $track->getId()) {
                $manager->remove($favoriteTrack);
                $manager->flush();

                return $this->json(
                    ['message' => 'Track removed from your favorites'],
                    200
                );
            }
        }

        $favoriteTrack = new FavoriteTrack();
        $favoriteTrack->setRelatedTo($currentUser);
        $favoriteTrack->setTrack($track);

        $manager->persist($favoriteTrack);
        $manager->flush();

        return $this->json(['message' => 'Track liked successfully']);
    }
}
