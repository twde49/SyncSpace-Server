<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\Track;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PlaylistService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Creates a new playlist for the user.
     *
     * @param User   $user The user entity
     * @param string $name The name of the playlist
     *
     * @return Playlist The created playlist entity
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
     * Adds a track to a playlist.
     *
     * @param int $playlistId The ID of the playlist
     * @param int $trackId    The YouTube ID of the track
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
