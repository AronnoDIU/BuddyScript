<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Page;
use CoreBundle\Entity\Community\PagePost;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PagePost>
 */
class PagePostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PagePost::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?PagePost
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.page', 'page')
            ->where('post.id = :id')
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
     * @return list<PagePost>
     */
    public function findByPage(Page $page, User $viewer, int $limit = 20): array
    {
        if (!$page->hasPermission($viewer, 'view')) {
            return [];
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->leftJoin('post.likes', 'like')
            ->addSelect('like')
            ->leftJoin('like.user', 'likeUser')
            ->addSelect('likeUser')
            ->leftJoin('post.comments', 'comment')
            ->addSelect('comment')
            ->leftJoin('comment.author', 'commentAuthor')
            ->addSelect('commentAuthor')
            ->leftJoin('comment.likes', 'commentLike')
            ->addSelect('commentLike')
            ->leftJoin('commentLike.user', 'commentLikeUser')
            ->addSelect('commentLikeUser')
            ->where('post.page = :page')
            ->setParameter('page', $page)
            ->orderBy('post.createdAt', 'DESC')
            ->addOrderBy('comment.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PagePost>
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.page', 'page')
            ->addSelect('page')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('post.author = :user')
            ->andWhere('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\PageMembership pm 
                WHERE pm.page = page.id AND pm.user = :user
            ) OR page.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PagePost>
     */
    public function searchInPage(Page $page, User $viewer, string $query, int $limit = 20): array
    {
        if (!$page->hasPermission($viewer, 'view')) {
            return [];
        }

        $search = '%' . mb_strtolower(trim($query)) . '%';

        return $this->createQueryBuilder('post')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->leftJoin('post.likes', 'like')
            ->addSelect('like')
            ->leftJoin('like.user', 'likeUser')
            ->addSelect('likeUser')
            ->where('post.page = :page')
            ->andWhere('LOWER(post.content) LIKE :search OR LOWER(CONCAT(author.firstName, author.lastName)) LIKE :search')
            ->setParameter('page', $page)
            ->setParameter('search', $search)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPostsInPage(Page $page): int
    {
        return (int) $this->createQueryBuilder('post')
            ->select('COUNT(post.id)')
            ->where('post.page = :page')
            ->setParameter('page', $page)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<PagePost>
     */
    public function findRecentPostsForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.page', 'page')
            ->addSelect('page')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\PageMembership pm 
                WHERE pm.page = page.id AND pm.user = :user
            )')
            ->setParameter('user', $user)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
