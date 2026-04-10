<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\EventPost;
use CoreBundle\Entity\Community\EventPostLike;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventPostLike>
 */
class EventPostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventPostLike::class);
    }

    public function findByPostAndUser(EventPost $post, User $user): ?EventPostLike
    {
        return $this->createQueryBuilder('like')
            ->where('like.post = :post')
            ->andWhere('like.user = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countLikesForPost(EventPost $post): int
    {
        return (int) $this->createQueryBuilder('like')
            ->select('COUNT(like.id)')
            ->where('like.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
