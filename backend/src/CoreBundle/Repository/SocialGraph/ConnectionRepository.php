<?php

declare(strict_types=1);

namespace CoreBundle\Repository\SocialGraph;

use CoreBundle\Entity\SocialGraph\Connection;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

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
            ->where('IDENTITY(connection.addressee) = :userId')
            ->andWhere('connection.status = :status')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
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
            ->where('IDENTITY(connection.requester) = :userId')
            ->andWhere('connection.status = :status')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
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
            ->where('(IDENTITY(connection.requester) = :userId OR IDENTITY(connection.addressee) = :userId)')
            ->andWhere('connection.status = :status')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->setParameter('status', Connection::STATUS_ACCEPTED)
            ->orderBy('connection.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

