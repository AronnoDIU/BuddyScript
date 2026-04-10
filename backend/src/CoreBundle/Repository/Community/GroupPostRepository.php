<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupPost;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<GroupPost>
 */
class GroupPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupPost::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?GroupPost
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.group', 'group')
            ->where('post.id = :id')
            ->andWhere('group.visibility = :public OR group.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('public', Group::VISIBILITY_PUBLIC)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GroupPost>
     */
    public function findByGroup(Group $group, User $viewer, int $limit = 20): array
    {
        if (!$group->hasPermission($viewer, 'view')) {
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
            ->where('post.group = :group')
            ->setParameter('group', $group)
            ->orderBy('post.createdAt', 'DESC')
            ->addOrderBy('comment.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupPost>
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.group', 'group')
            ->addSelect('group')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('post.author = :user')
            ->andWhere('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            ) OR group.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupPost>
     */
    public function searchInGroup(Group $group, User $viewer, string $query, int $limit = 20): array
    {
        if (!$group->hasPermission($viewer, 'view')) {
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
            ->where('post.group = :group')
            ->andWhere('LOWER(post.content) LIKE :search OR LOWER(CONCAT(author.firstName, author.lastName)) LIKE :search')
            ->setParameter('group', $group)
            ->setParameter('search', $search)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPostsInGroup(Group $group): int
    {
        return (int) $this->createQueryBuilder('post')
            ->select('COUNT(post.id)')
            ->where('post.group = :group')
            ->setParameter('group', $group)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<GroupPost>
     */
    public function findRecentPostsForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.group', 'group')
            ->addSelect('group')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            )')
            ->setParameter('user', $user)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
