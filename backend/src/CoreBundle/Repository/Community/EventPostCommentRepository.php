<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\EventPost;
use CoreBundle\Entity\Community\EventPostComment;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<EventPostComment>
 */
class EventPostCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventPostComment::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?EventPostComment
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('comment')
            ->innerJoin('comment.post', 'post')
            ->innerJoin('post.event', 'event')
            ->where('comment.id = :id')
            ->andWhere('event.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\EventMembership em 
                WHERE em.event = event.id AND em.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countCommentsInPost(EventPost $post): int
    {
        return (int) $this->createQueryBuilder('comment')
            ->select('COUNT(comment.id)')
            ->where('comment.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
