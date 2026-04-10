<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Page;
use CoreBundle\Entity\Community\PageMembership;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PageMembership>
 */
class PageMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageMembership::class);
    }

    public function findByUserAndPage(User $user, Page $page): ?PageMembership
    {
        return $this->createQueryBuilder('membership')
            ->where('membership.user = :user')
            ->andWhere('membership.page = :page')
            ->setParameter('user', $user)
            ->setParameter('page', $page)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<PageMembership>
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.page', 'page')
            ->addSelect('page')
            ->where('membership.user = :user')
            ->setParameter('user', $user)
            ->orderBy('membership.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PageMembership>
     */
    public function findByPage(Page $page, int $limit = 50): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->addSelect('user')
            ->where('membership.page = :page')
            ->setParameter('page', $page)
            ->orderBy('membership.role', 'ASC')
            ->addOrderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PageMembership>
     */
    public function findByRole(Page $page, string $role, int $limit = 20): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->addSelect('user')
            ->where('membership.page = :page')
            ->andWhere('membership.role = :role')
            ->setParameter('page', $page)
            ->setParameter('role', $role)
            ->orderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<User>
     */
    public function findPageMembers(Page $page, string $role = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->where('membership.page = :page')
            ->setParameter('page', $page);

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

    public function countMembersByRole(Page $page, string $role): int
    {
        return (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->where('membership.page = :page')
            ->andWhere('membership.role = :role')
            ->setParameter('page', $page)
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{admins:int, editors:int, members:int}
     */
    public function getMemberStats(Page $page): array
    {
        $qb = $this->createQueryBuilder('membership')
            ->select('membership.role, COUNT(membership.id) as count')
            ->where('membership.page = :page')
            ->setParameter('page', $page)
            ->groupBy('membership.role');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'admins' => 0,
            'editors' => 0,
            'members' => 0,
        ];

        foreach ($results as $result) {
            $role = $result['role'];
            $count = (int) $result['count'];

            match ($role) {
                Page::ROLE_ADMIN => $stats['admins'] = $count,
                Page::ROLE_EDITOR => $stats['editors'] = $count,
                Page::ROLE_MEMBER => $stats['members'] = $count,
                default => null,
            };
        }

        return $stats;
    }
}
