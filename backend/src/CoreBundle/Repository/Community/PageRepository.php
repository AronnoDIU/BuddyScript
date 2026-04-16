<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Page;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?Page
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('page')
            ->where('page.id = :id')
            ->andWhere('page.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\PageMembership pm
                WHERE pm.page = page.id AND pm.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Page>
     */
    public function findPagesForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('page')
            ->innerJoin('page.memberships', 'membership')
            ->where('membership.user = :user')
            ->setParameter('user', $user)
            ->orderBy('membership.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function findPublicPages(int $limit = 50): array
    {
        return $this->createQueryBuilder('page')
            ->orderBy('page.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function searchPages(string $query, User $user, int $limit = 20): array
    {
        $search = '%' . mb_strtolower(trim($query)) . '%';

        return $this->createQueryBuilder('page')
            ->where('LOWER(page.name) LIKE :search OR LOWER(page.description) LIKE :search')
            ->andWhere('page.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\PageMembership pm
                WHERE pm.page = page.id AND pm.user = :user
            )')
            ->setParameter('search', $search)
            ->setParameter('user', $user)
            ->orderBy('page.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function findPagesByCreator(User $creator, int $limit = 20): array
    {
        return $this->createQueryBuilder('page')
            ->where('page.creator = :creator')
            ->setParameter('creator', $creator)
            ->orderBy('page.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function findByCategory(string $category, int $limit = 20): array
    {
        return $this->createQueryBuilder('page')
            ->where('page.category = :category')
            ->setParameter('category', $category)
            ->orderBy('page.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{pagesCount:int, createdPagesCount:int}
     */
    public function buildPageStatsForUser(User $user): array
    {
        $membershipQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Page::class, 'p')
            ->innerJoin('p.memberships', 'pm')
            ->where('pm.user = :user')
            ->setParameter('user', $user);

        $createdQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Page::class, 'p')
            ->where('p.creator = :user')
            ->setParameter('user', $user);

        return [
            'pagesCount' => (int) $membershipQb->getQuery()->getSingleScalarResult(),
            'createdPagesCount' => (int) $createdQb->getQuery()->getSingleScalarResult(),
        ];
    }
}
