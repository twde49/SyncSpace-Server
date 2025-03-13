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
     * @return Conversation[] Returns an array of Conversation objects
     */
    public function checkIfAlreadyExists(array $userIds, int $currentUserId): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
    
        $queryBuilder->andWhere($queryBuilder->expr()->in('c.user', ':userIds'))
                     ->setParameter('userIds', $userIds);
    
        $queryBuilder->andWhere('c.user = :currentUserId')
                     ->setParameter('currentUserId', $currentUserId);
    
        $queryBuilder->orderBy('c.id', 'ASC')
                     ->setMaxResults(10);
    
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
