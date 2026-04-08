<?php

declare(strict_types=1);

namespace CoreBundle\Repository;

use CoreBundle\Entity\Reaction;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Reaction>
 */
class ReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reaction::class);
    }

    public function findOneByTargetAndUser(string $targetType, Uuid $targetId, User $user): ?Reaction
    {
        return $this->createQueryBuilder('reaction')
            ->where('reaction.targetType = :targetType')
            ->andWhere('reaction.targetId = :targetId')
            ->andWhere('reaction.user = :user')
            ->setParameter('targetType', $targetType)
            ->setParameter('targetId', $targetId, UuidType::NAME)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Reaction>
     */
    public function findByTarget(string $targetType, Uuid $targetId): array
    {
        return $this->createQueryBuilder('reaction')
            ->innerJoin('reaction.user', 'actor')->addSelect('actor')
            ->where('reaction.targetType = :targetType')
            ->andWhere('reaction.targetId = :targetId')
            ->setParameter('targetType', $targetType)
            ->setParameter('targetId', $targetId, UuidType::NAME)
            ->orderBy('reaction.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

