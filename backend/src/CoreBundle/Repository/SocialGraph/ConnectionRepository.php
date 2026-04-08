<?php

declare(strict_types=1);

namespace CoreBundle\Repository\SocialGraph;

use CoreBundle\Entity\SocialGraph\Connection;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Connection>
 */
class ConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connection::class);
    }

    public function findBetweenUsers(User $userA, User $userB): ?Connection
    {
        return $this->createQueryBuilder('connection')
            ->where('(connection.requester = :userA AND connection.addressee = :userB) OR (connection.requester = :userB AND connection.addressee = :userA)')
            ->setParameter('userA', $userA)
            ->setParameter('userB', $userB)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Connection>
     */
    public function findIncomingPending(User $user): array
    {
        return $this->createQueryBuilder('connection')
            ->innerJoin('connection.requester', 'requester')->addSelect('requester')
            ->where('connection.addressee = :user')
            ->andWhere('connection.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Connection::STATUS_PENDING)
            ->orderBy('connection.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Connection>
     */
    public function findOutgoingPending(User $user): array
    {
        return $this->createQueryBuilder('connection')
            ->innerJoin('connection.addressee', 'addressee')->addSelect('addressee')
            ->where('connection.requester = :user')
            ->andWhere('connection.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Connection::STATUS_PENDING)
            ->orderBy('connection.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Connection>
     */
    public function findAccepted(User $user): array
    {
        return $this->createQueryBuilder('connection')
            ->innerJoin('connection.requester', 'requester')->addSelect('requester')
            ->innerJoin('connection.addressee', 'addressee')->addSelect('addressee')
            ->where('(connection.requester = :user OR connection.addressee = :user)')
            ->andWhere('connection.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Connection::STATUS_ACCEPTED)
            ->orderBy('connection.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

