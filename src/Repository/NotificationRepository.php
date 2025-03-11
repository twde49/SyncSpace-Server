<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

        /**
         * @return Notification[] Returns an array of Notification objects
         * @param User $user
         */
        public function getUnreadNotifications(User $user): array
        {
            return $this->createQueryBuilder('n')
                ->andWhere('n.isRead = :val')
                ->andWhere('n.relatedTo = :user')
                ->setParameter('val', false)
                ->setParameter('user', $user)
                ->orderBy('n.id', 'DESC')
                ->getQuery()
                ->getResult()
            ;
        }

    //    public function findOneBySomeField($value): ?Notification
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
