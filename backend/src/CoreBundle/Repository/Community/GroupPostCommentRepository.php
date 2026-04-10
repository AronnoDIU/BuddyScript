<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\GroupPost;
use CoreBundle\Entity\Community\GroupPostComment;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<GroupPostComment>
 */
class GroupPostCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupPostComment::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?GroupPostComment
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('comment')
            ->innerJoin('comment.post', 'post')
            ->innerJoin('post.group', 'group')
            ->where('comment.id = :id')
            ->andWhere('group.visibility = :public OR group.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('public', Group::VISIBILITY_PUBLIC)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<GroupPostComment>
     */
    public function findByPost(GroupPost $post, User $viewer, int $limit = 50): array
    {
        return $this->createQueryBuilder('comment')
            ->innerJoin('comment.author', 'author')
            ->addSelect('author')
            ->leftJoin('comment.likes', 'like')
            ->addSelect('like')
            ->leftJoin('like.user', 'likeUser')
            ->addSelect('likeUser')
            ->leftJoin('comment.replies', 'reply')
            ->addSelect('reply')
            ->leftJoin('reply.author', 'replyAuthor')
            ->addSelect('replyAuthor')
            ->leftJoin('reply.likes', 'replyLike')
            ->addSelect('replyLike')
            ->leftJoin('replyLike.user', 'replyLikeUser')
            ->addSelect('replyLikeUser')
            ->where('comment.post = :post')
            ->andWhere('comment.parent IS NULL')
            ->setParameter('post', $post)
            ->orderBy('comment.createdAt', 'ASC')
            ->addOrderBy('reply.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<GroupPostComment>
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('comment')
            ->innerJoin('comment.post', 'post')
            ->addSelect('post')
            ->innerJoin('post.group', 'group')
            ->addSelect('group')
            ->innerJoin('comment.author', 'author')
            ->addSelect('author')
            ->where('comment.author = :user')
            ->andWhere('EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\GroupMembership gm 
                WHERE gm.group = group.id AND gm.user = :user
            ) OR group.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('comment.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countCommentsInPost(GroupPost $post): int
    {
        return (int) $this->createQueryBuilder('comment')
            ->select('COUNT(comment.id)')
            ->where('comment.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<GroupPostComment>
     */
    public function findReplies(GroupPostComment $comment, int $limit = 20): array
    {
        return $this->createQueryBuilder('reply')
            ->innerJoin('reply.author', 'author')
            ->addSelect('author')
            ->leftJoin('reply.likes', 'like')
            ->addSelect('like')
            ->leftJoin('like.user', 'likeUser')
            ->addSelect('likeUser')
            ->where('reply.parent = :comment')
            ->setParameter('comment', $comment)
            ->orderBy('reply.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
