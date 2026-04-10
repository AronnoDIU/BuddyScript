<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupMembership;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<GroupMembership>
 */
class GroupMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMembership::class);
    }

    public function findByUserAndGroup(User $user, Group $group): ?GroupMembership
    {
        return $this->createQueryBuilder('membership')
            ->where('IDENTITY(membership.user) = :userId')
            ->andWhere('IDENTITY(membership.group) = :groupId')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->setParameter('groupId', $group->getId(), UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GroupMembership>
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.group', 'grp')
            ->addSelect('grp')
            ->where('IDENTITY(membership.user) = :userId')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->orderBy('membership.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupMembership>
     */
    public function findByGroup(Group $group, int $limit = 50): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->addSelect('user')
            ->where('IDENTITY(membership.group) = :groupId')
            ->setParameter('groupId', $group->getId(), UuidType::NAME)
            ->orderBy('membership.role', 'ASC')
            ->addOrderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupMembership>
     */
    public function findByRole(Group $group, string $role, int $limit = 20): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->addSelect('user')
            ->where('IDENTITY(membership.group) = :groupId')
            ->andWhere('membership.role = :role')
            ->setParameter('groupId', $group->getId(), UuidType::NAME)
            ->setParameter('role', $role)
            ->orderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<User>
     */
    public function findGroupMembers(Group $group, ?string $role = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->where('IDENTITY(membership.group) = :groupId')
            ->setParameter('groupId', $group->getId(), UuidType::NAME);

        if ($role !== null) {
            $qb->andWhere('membership.role = :role')
               ->setParameter('role', $role);
        }

        return $qb->orderBy('membership.role', 'ASC')
            ->addOrderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countMembersByRole(Group $group, string $role): int
    {
        return (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->where('IDENTITY(membership.group) = :groupId')
            ->andWhere('membership.role = :role')
            ->setParameter('groupId', $group->getId(), UuidType::NAME)
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{admins:int, moderators:int, members:int}
     */
    public function getMemberStats(Group $group): array
    {
        $qb = $this->createQueryBuilder('membership')
            ->select('membership.role, COUNT(membership.id) as count')
            ->where('IDENTITY(membership.group) = :groupId')
            ->setParameter('groupId', $group->getId(), UuidType::NAME)
            ->groupBy('membership.role');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'admins' => 0,
            'moderators' => 0,
            'members' => 0,
        ];

        foreach ($results as $result) {
            $role = $result['role'];
            $count = (int) $result['count'];

            match ($role) {
                Group::ROLE_ADMIN => $stats['admins'] = $count,
                Group::ROLE_MODERATOR => $stats['moderators'] = $count,
                Group::ROLE_MEMBER => $stats['members'] = $count,
                default => null,
            };
        }

        return $stats;
    }
}
