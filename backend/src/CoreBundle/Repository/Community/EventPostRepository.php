<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Event;
use CoreBundle\Entity\Community\EventPost;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<EventPost>
 */
class EventPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventPost::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?EventPost
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.event', 'event')
            ->where('post.id = :id')
            ->andWhere('event.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\EventMembership em 
                WHERE em.event = event.id AND em.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<EventPost>
     */
    public function findByEvent(Event $event, User $viewer, int $limit = 20): array
    {
        if (!$event->hasPermission($viewer, 'view')) {
            return [];
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->leftJoin('post.likes', 'like')
            ->addSelect('like')
            ->leftJoin('like.user', 'likeUser')
            ->addSelect('likeUser')
            ->leftJoin('post.comments', 'comment')
            ->addSelect('comment')
            ->leftJoin('comment.author', 'commentAuthor')
            ->addSelect('commentAuthor')
            ->leftJoin('comment.likes', 'commentLike')
            ->addSelect('commentLike')
            ->leftJoin('commentLike.user', 'commentLikeUser')
            ->addSelect('commentLikeUser')
            ->where('post.event = :event')
            ->setParameter('event', $event)
            ->orderBy('post.createdAt', 'DESC')
            ->addOrderBy('comment.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<EventPost>
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.event', 'event')
            ->addSelect('event')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('post.author = :user')
            ->andWhere('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\EventMembership em 
                WHERE em.event = event.id AND em.user = :user
            ) OR event.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPostsInEvent(Event $event): int
    {
        return (int) $this->createQueryBuilder('post')
            ->select('COUNT(post.id)')
            ->where('post.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<EventPost>
     */
    public function findRecentPostsForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('post')
            ->innerJoin('post.event', 'event')
            ->addSelect('event')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\EventMembership em 
                WHERE em.event = event.id AND em.user = :user
            )')
            ->setParameter('user', $user)
            ->orderBy('post.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
