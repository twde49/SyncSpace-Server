<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Track;
use App\Entity\TrackHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TrackHistoryService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function addTrackToHistory(User $user, int $trackId): void
    {
        $track = $this->em->getRepository(Track::class)->find($trackId);
        if (!$track) {
            return;
        }

        $history = new TrackHistory();
        $history->setOfUser($user);
        $history->setTrack($track);
        $history->setPlayedAt(new \DateTimeImmutable());

        $this->em->persist($history);
        $this->em->flush();
    }

    public function getUserHistory(User $user): array
    {
        return $this->em->getRepository(TrackHistory::class)
            ->findBy(['ofUser' => $user], ['playedAt' => 'DESC']);
    }
}
