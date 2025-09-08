<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FavoriteTrack;
use App\Entity\User;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteTrackService
{
    /**
     * Constructor for FavoriteTrackService.
     *
     * @param EntityManagerInterface $entityManager   The entity manager
     * @param TrackRepository        $trackRepository The track repository
     */
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly TrackRepository $trackRepository)
    {
    }

    /**
     * Updates the user's favorite tracks by adding or removing a track.
     *
     * @param User   $user    The user entity
     * @param string $trackId The YouTube ID of the track
     */
    public function updateFavorite(User $user, string $trackId): void
    {
        $favorites = $user->getFavoriteTracks();
        $track = $this->trackRepository->findOneBy(['youtubeId' => $trackId]);

        if ($track) {
            foreach ($favorites as $favorite) {
                switch ($favorite->getTrack()->getYoutubeId() === $track->getYoutubeId()) {
                    case true:
                        $this->entityManager->remove($favorite);
                        $this->entityManager->flush();
                        break;
                    case false:
                        $newFavorite = new FavoriteTrack();
                        $newFavorite->setRelatedTo($user);
                        $newFavorite->setTrack($track);
                        $this->entityManager->persist($newFavorite);
                        break;
                }
            }
        }

        $this->entityManager->flush();
    }
}
