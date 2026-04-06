<?php

declare(strict_types=1);

namespace App\Repository\Comment;

use App\Entity\Comment;
use App\Entity\Comment\Like;
use App\Entity\User;
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
