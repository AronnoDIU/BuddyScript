<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Comment;

use CoreBundle\Entity\Comment;
use CoreBundle\Entity\Comment\Like;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Like>
 */
class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    public function findOneByCommentAndUser(Comment $comment, User $user): ?Like
    {
        return $this->findOneBy(['comment' => $comment, 'user' => $user]);
    }
}
