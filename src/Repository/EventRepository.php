<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function getAllEventsWhereUserIsIn(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
                SELECT * FROM event
                WHERE event.organizer_id = :user_id
                AND event.id in (SELECT event_id FROM event_user WHERE user_id = :user_id)
                ';

        $resultSet = $conn->executeQuery($sql, ['user_id' => $user->getId()]);

        return $resultSet->fetchAllAssociative();
    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
