<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\PagePost;
use CoreBundle\Entity\Community\PagePostLike;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PagePostLike>
 */
class PagePostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PagePostLike::class);
    }

    public function findByPostAndUser(PagePost $post, User $user): ?PagePostLike
    {
        return $this->createQueryBuilder('like')
            ->where('like.post = :post')
            ->andWhere('like.user = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countLikesForPost(PagePost $post): int
    {
        return (int) $this->createQueryBuilder('like')
            ->select('COUNT(like.id)')
            ->where('like.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
