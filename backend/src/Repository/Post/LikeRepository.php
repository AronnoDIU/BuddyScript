<?php

declare(strict_types=1);

namespace App\Repository\Post;

use App\Entity\Post;
use App\Entity\Post\Like;
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

    public function findOneByPostAndUser(Post $post, User $user): ?Like
    {
        return $this->findOneBy(['post' => $post, 'user' => $user]);
    }
}
