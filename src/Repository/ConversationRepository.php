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
        public function checkIfAlreadyExists(
            array $userIds,
            int $currentUserId
        ): array {
            $queryBuilder = $this->createQueryBuilder("c");

            $queryBuilder->join("c.users", "u");

            $queryBuilder
                ->andWhere(":currentUserId MEMBER OF c.users")
                ->setParameter("currentUserId", $currentUserId);

            $queryBuilder
                ->andWhere($queryBuilder->expr()->in("u.id", ":userIds"))
                ->setParameter("userIds", $userIds);

            $queryBuilder
                ->groupBy("c.id")
                ->having("COUNT(DISTINCT u.id) = :userCount")
                ->setParameter("userCount", count($userIds));

            $queryBuilder->orderBy("c.id", "ASC")->setMaxResults(10);

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
