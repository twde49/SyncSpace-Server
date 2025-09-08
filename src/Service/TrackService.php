<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Track;
use Doctrine\ORM\EntityManagerInterface;

class TrackService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Crée ou récupère un track selon le youtubeId (évite doublons).
     *
     * @param string $youtubeId L'identifiant YouTube du track
     * @param string $title     Le titre du track
     * @param string $artist    L'artiste du track (optionnel)
     * @param string $genre     Le genre du track (optionnel)
     *
     * @return Track Le track créé ou récupéré
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
