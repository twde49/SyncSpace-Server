<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

        /**
         * Check if a conversation already exists with the given user IDs.
         *
         * @param array $userIds An array of user IDs to check within the conversation participants.
         * @param int $currentUserId The ID of the current user.
         *
         * @return Conversation[] Returns an array of Conversation objects.
         */
        public function checkIfAlreadyExists(array $userIds, int $currentUserId): array
        {
            $allUserIds = array_merge($userIds, [$currentUserId]);
            sort($allUserIds);

            $queryBuilder = $this->createQueryBuilder('c')
                ->join('c.users', 'uAll')
                ->join('c.users', 'uMatch', 'WITH', 'uMatch.id IN (:userIds)')
                ->groupBy('c.id')
                ->having('COUNT(DISTINCT uAll.id) = :userCount')
                ->andHaving('COUNT(DISTINCT uMatch.id) = :userCount')
                ->setParameter('userIds', $allUserIds)
                ->setParameter('userCount', count($allUserIds));

            return $queryBuilder->getQuery()->getResult();
        }


    //    public function findOneBySomeField($value): ?Conversation
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
