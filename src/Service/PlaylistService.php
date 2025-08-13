<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\Track;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PlaylistService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param User   $user
     * @param string $name
     */
    public function createPlaylist(User $user, $name): Playlist
    {
        $playlist = new Playlist();
        $playlist->setName($name);

        $playlist->setRelatedTo($user);

        $this->em->persist($playlist);
        $this->em->flush();

        return $playlist;
    }

    /**
     * @param int $playlistId
     * @param int $trackId
     */
    public function addTrackToPlaylist($playlistId, $trackId): void
    {
        $playlist = $this->em->getRepository(Playlist::class)->find($playlistId);
        $track = $this->em->getRepository(Track::class)->findOneBy(['youtubeId' => $trackId]);

        if ($playlist && $track) {
            $playlist->getTracks()->add($track);
            $this->em->flush();
        }
    }
}
