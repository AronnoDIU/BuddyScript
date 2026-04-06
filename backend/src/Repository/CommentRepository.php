<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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

        $comment = $this->find($uuid);
        if (!$comment instanceof Comment) {
            return null;
        }

        $post = $comment->getPost();
        if ($post->getVisibility() === Post::VISIBILITY_PUBLIC || $post->getAuthor()->id->equals($user->id)) {
            return $comment;
        }

        return null;
    }
}
