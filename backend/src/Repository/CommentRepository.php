<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?Comment
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('comment')
            ->innerJoin('comment.post', 'post')
            ->addSelect('post')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('comment.id = :id')
            ->andWhere('post.visibility = :publicVisibility OR author = :viewer')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('publicVisibility', Post::VISIBILITY_PUBLIC)
            ->setParameter('viewer', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
