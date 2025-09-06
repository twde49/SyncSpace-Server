<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FavoriteTrack;
use App\Entity\User;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteTrackService
{
    private EntityManagerInterface $entityManager;

    private TrackRepository $trackRepository;

    public function __construct(EntityManagerInterface $entityManager, TrackRepository $trackRepository)
    {
        $this->entityManager = $entityManager;
        $this->trackRepository = $trackRepository;
    }

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
