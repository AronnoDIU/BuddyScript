<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\EventPostComment;
use CoreBundle\Entity\Community\EventPostCommentLike;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventPostCommentLike>
 */
class EventPostCommentLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventPostCommentLike::class);
    }

    public function findByCommentAndUser(EventPostComment $comment, User $user): ?EventPostCommentLike
    {
        return $this->createQueryBuilder('like')
            ->where('like.comment = :comment')
            ->andWhere('like.user = :user')
            ->setParameter('comment', $comment)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countLikesForComment(EventPostComment $comment): int
    {
        return (int) $this->createQueryBuilder('like')
            ->select('COUNT(like.id)')
            ->where('like.comment = :comment')
            ->setParameter('comment', $comment)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
