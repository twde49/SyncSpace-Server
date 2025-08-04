<?php

namespace App\Service;

use App\Entity\FavoriteTrack;
use App\Entity\Track;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteTrackService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFavoritesByUser(User $user): ?FavoriteTrack
    {
        return $this->em->getRepository(FavoriteTrack::class)->findOneBy(['relatedTo' => $user]);
    }

    public function addTrackToFavorites(User $user, string $trackId): void
    {
        $favorites = $this->getFavoritesByUser($user);
        $track = $this->em->getRepository(Track::class)->findBy(['youtubeId' => $trackId]);

        if (!$favorites) {
            $favorites = new FavoriteTrack();
            $favorites->setRelatedTo($user);
            $this->em->persist($favorites);
        }

        if ($track && !$favorites->getTrack()->contains($track)) {
            $favorites->addTrack($track);
        }

        $this->em->flush();
    }

    public function removeTrackFromFavorites(User $user, string $trackId): void
    {
        $favorites = $this->getFavoritesByUser($user);
        $track = $this->em->getRepository(Track::class)->find($trackId);

        if ($favorites && $track && $favorites->getTrack()->contains($track)) {
            $favorites->removeTrack($track);
            $this->em->flush();
        }
    }
}
