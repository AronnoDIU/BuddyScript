<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?Group
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('grp')
            ->where('grp.id = :id')
            ->andWhere('grp.visibility = :publicVisibility OR IDENTITY(grp.creator) = :viewerId OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm
                WHERE gm.group = grp.id AND IDENTITY(gm.user) = :viewerId
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('publicVisibility', Group::VISIBILITY_PUBLIC)
            ->setParameter('viewerId', $user->getId(), UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Group>
     */
    public function findGroupsForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('grp')
            ->innerJoin('grp.memberships', 'membership')
            ->where('IDENTITY(membership.user) = :userId')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->orderBy('membership.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Group>
     */
    public function findPublicGroups(int $limit = 50): array
    {
        return $this->createQueryBuilder('grp')
            ->where('grp.visibility = :public')
            ->setParameter('public', Group::VISIBILITY_PUBLIC)
            ->orderBy('grp.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Group>
     */
    public function searchGroups(string $query, User $user, int $limit = 20): array
    {
        $search = '%' . mb_strtolower(trim($query)) . '%';

        return $this->createQueryBuilder('grp')
            ->where('LOWER(grp.name) LIKE :search OR LOWER(grp.description) LIKE :search')
            ->andWhere('grp.visibility = :public OR IDENTITY(grp.creator) = :viewerId OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm
                WHERE gm.group = grp.id AND IDENTITY(gm.user) = :viewerId
            )')
            ->setParameter('search', $search)
            ->setParameter('public', Group::VISIBILITY_PUBLIC)
            ->setParameter('viewerId', $user->getId(), UuidType::NAME)
            ->orderBy('grp.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Group>
     */
    public function findGroupsByCreator(User $creator, int $limit = 20): array
    {
        return $this->createQueryBuilder('grp')
            ->where('IDENTITY(grp.creator) = :creatorId')
            ->setParameter('creatorId', $creator->getId(), UuidType::NAME)
            ->orderBy('grp.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{groupsCount:int, publicGroupsCount:int, privateGroupsCount:int, secretGroupsCount:int}
     */
    public function buildGroupStatsForUser(User $user): array
    {
        $membershipQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Group::class, 'g')
            ->innerJoin('g.memberships', 'gm')
            ->where('IDENTITY(gm.user) = :userId')
            ->setParameter('userId', $user->getId(), UuidType::NAME);

        $createdQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Group::class, 'g')
            ->where('IDENTITY(g.creator) = :userId')
            ->setParameter('userId', $user->getId(), UuidType::NAME);

        $publicQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Group::class, 'g')
            ->where('g.visibility = :public')
            ->setParameter('public', Group::VISIBILITY_PUBLIC);

        return [
            'groupsCount' => (int) $membershipQb->getQuery()->getSingleScalarResult(),
            'createdGroupsCount' => (int) $createdQb->getQuery()->getSingleScalarResult(),
            'publicGroupsCount' => (int) $publicQb->getQuery()->getSingleScalarResult(),
        ];
    }
}
