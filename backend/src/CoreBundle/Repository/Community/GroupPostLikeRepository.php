<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\GroupPost;
use CoreBundle\Entity\Community\GroupPostLike;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupPostLike>
 */
class GroupPostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupPostLike::class);
    }

    public function findByPostAndUser(GroupPost $post, User $user): ?GroupPostLike
    {
        return $this->createQueryBuilder('like')
            ->where('like.post = :post')
            ->andWhere('like.user = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GroupPostLike>
     */
    public function findByPost(GroupPost $post, int $limit = 50): array
    {
        return $this->createQueryBuilder('like')
            ->innerJoin('like.user', 'user')
            ->addSelect('user')
            ->where('like.post = :post')
            ->setParameter('post', $post)
            ->orderBy('like.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupPostLike>
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('like')
            ->innerJoin('like.post', 'post')
            ->addSelect('post')
            ->innerJoin('post.group', 'grp')
            ->addSelect('grp')
            ->where('like.user = :user')
            ->setParameter('user', $user)
            ->orderBy('like.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countLikesForPost(GroupPost $post): int
    {
        return (int) $this->createQueryBuilder('like')
            ->select('COUNT(like.id)')
            ->where('like.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
