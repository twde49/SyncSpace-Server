<?php

namespace App\Service;

use App\Entity\Track;
use Doctrine\ORM\EntityManagerInterface;

class TrackService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Crée ou récupère un track selon le youtubeId (évite doublons).
     */
    public function createOrGetTrack(string $youtubeId, string $title, string $artist = '', string $genre = ''): Track
    {
        $trackRepo = $this->em->getRepository(Track::class);
        $track = $trackRepo->findOneBy(['youtubeId' => $youtubeId]);

        if (!$track) {
            $track = new Track();
            $track->setYoutubeId($youtubeId);
            $track->setTitle($title);
            $track->setArtist($artist);

            $this->em->persist($track);
            $this->em->flush();
        }

        return $track;
    }
}
