<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\PagePost;
use CoreBundle\Entity\Community\PagePostComment;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PagePostComment>
 */
class PagePostCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PagePostComment::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?PagePostComment
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('comment')
            ->innerJoin('comment.post', 'post')
            ->innerJoin('post.page', 'page')
            ->where('comment.id = :id')
            ->andWhere('page.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\PageMembership pm 
                WHERE pm.page = page.id AND pm.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countCommentsInPost(PagePost $post): int
    {
        return (int) $this->createQueryBuilder('comment')
            ->select('COUNT(comment.id)')
            ->where('comment.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
