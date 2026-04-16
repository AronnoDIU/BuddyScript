<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Safety;

use CoreBundle\Entity\Safety\UserBlock;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBlock>
 */
class UserBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBlock::class);
    }

    public function findByUsers(User $blocker, User $blocked): ?UserBlock
    {
        return $this->createQueryBuilder('userBlock')
            ->where('userBlock.blocker = :blocker')
            ->andWhere('userBlock.blocked = :blocked')
            ->setParameter('blocker', $blocker)
            ->setParameter('blocked', $blocked)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<UserBlock>
     */
    public function findByBlocker(User $blocker, int $limit = 200): array
    {
        return $this->createQueryBuilder('userBlock')
            ->innerJoin('userBlock.blocked', 'blocked')
            ->addSelect('blocked')
            ->where('userBlock.blocker = :blocker')
            ->setParameter('blocker', $blocker)
            ->orderBy('userBlock.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

