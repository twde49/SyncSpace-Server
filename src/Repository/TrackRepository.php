<?php

namespace App\Repository;

use App\Entity\Track;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Track>
 */
class TrackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Track::class);
    }

    /**
     * Recherche de piste par mot-clé.
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.title LIKE :kw OR t.artist LIKE :kw OR t.genre LIKE :kw')
            ->setParameter('kw', '%'.$keyword.'%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Suggestions basées sur un genre.
     */
    public function suggestByGenre(string $genre, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.genre = :genre')
            ->setParameter('genre', $genre)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
