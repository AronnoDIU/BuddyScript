<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\PagePostComment;
use CoreBundle\Entity\Community\PagePostCommentLike;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PagePostCommentLike>
 */
class PagePostCommentLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PagePostCommentLike::class);
    }

    public function findByCommentAndUser(PagePostComment $comment, User $user): ?PagePostCommentLike
    {
        return $this->createQueryBuilder('like')
            ->where('like.comment = :comment')
            ->andWhere('like.user = :user')
            ->setParameter('comment', $comment)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countLikesForComment(PagePostComment $comment): int
    {
        return (int) $this->createQueryBuilder('like')
            ->select('COUNT(like.id)')
            ->where('like.comment = :comment')
            ->setParameter('comment', $comment)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
