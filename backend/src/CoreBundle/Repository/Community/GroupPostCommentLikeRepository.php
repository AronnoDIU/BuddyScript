<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\GroupPostComment;
use CoreBundle\Entity\Community\GroupPostCommentLike;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupPostCommentLike>
 */
class GroupPostCommentLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupPostCommentLike::class);
    }

    public function findByCommentAndUser(GroupPostComment $comment, User $user): ?GroupPostCommentLike
    {
        return $this->createQueryBuilder('like')
            ->where('like.comment = :comment')
            ->andWhere('like.user = :user')
            ->setParameter('comment', $comment)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GroupPostCommentLike>
     */
    public function findByComment(GroupPostComment $comment, int $limit = 50): array
    {
        return $this->createQueryBuilder('like')
            ->innerJoin('like.user', 'user')
            ->addSelect('user')
            ->where('like.comment = :comment')
            ->setParameter('comment', $comment)
            ->orderBy('like.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupPostCommentLike>
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('like')
            ->innerJoin('like.comment', 'comment')
            ->addSelect('comment')
            ->innerJoin('comment.post', 'post')
            ->addSelect('post')
            ->innerJoin('post.group', 'group')
            ->addSelect('group')
            ->where('like.user = :user')
            ->setParameter('user', $user)
            ->orderBy('like.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countLikesForComment(GroupPostComment $comment): int
    {
        return (int) $this->createQueryBuilder('like')
            ->select('COUNT(like.id)')
            ->where('like.comment = :comment')
            ->setParameter('comment', $comment)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
