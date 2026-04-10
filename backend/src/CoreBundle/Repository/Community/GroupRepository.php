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

        return $this->createQueryBuilder('group')
            ->where('group.id = :id')
            ->andWhere('group.visibility = :publicVisibility OR group.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('publicVisibility', Group::VISIBILITY_PUBLIC)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Group>
     */
    public function findGroupsForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('group')
            ->innerJoin('group.memberships', 'membership')
            ->where('membership.user = :user')
            ->setParameter('user', $user)
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
        return $this->createQueryBuilder('group')
            ->where('group.visibility = :public')
            ->setParameter('public', Group::VISIBILITY_PUBLIC)
            ->orderBy('group.createdAt', 'DESC')
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

        return $this->createQueryBuilder('group')
            ->where('LOWER(group.name) LIKE :search OR LOWER(group.description) LIKE :search')
            ->andWhere('group.visibility = :public OR group.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            )')
            ->setParameter('search', $search)
            ->setParameter('public', Group::VISIBILITY_PUBLIC)
            ->setParameter('user', $user)
            ->orderBy('group.memberCount', 'DESC')
            ->addOrderBy('group.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Group>
     */
    public function findGroupsByCreator(User $creator, int $limit = 20): array
    {
        return $this->createQueryBuilder('group')
            ->where('group.creator = :creator')
            ->setParameter('creator', $creator)
            ->orderBy('group.createdAt', 'DESC')
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
            ->where('gm.user = :user')
            ->setParameter('user', $user);

        $createdQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(g.id)')
            ->from(Group::class, 'g')
            ->where('g.creator = :user')
            ->setParameter('user', $user);

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
