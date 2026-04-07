<?php

declare(strict_types=1);

namespace CoreBundle\Repository;

use CoreBundle\Entity\Post;
use CoreBundle\Entity\Post\Like as PostLikeEntity;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use CoreBundle\Entity\Comment as CommentEntity;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return list<Post>
     */
    public function findFeedForUser(User $viewer, int $limit = 50): array
    {
        $commentsByViewerDql = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(CommentEntity::class, 'vc')
            ->where('vc.post = p')
            ->andWhere('vc.author = :viewer')
            ->getDQL();

        $postIdRows = $this->createQueryBuilder('p')
            ->select('p.id AS id')
            ->where('p.visibility = :public OR p.author = :viewer OR EXISTS(' . $commentsByViewerDql . ')')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('viewer', $viewer)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $postIds = array_map(static fn(array $row): ?string => $row['id'] ?? null, $postIdRows)
                |> array_filter(...)
                |> array_values(...);
        if ($postIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->leftJoin('p.likes', 'pl')->addSelect('pl')
            ->leftJoin('pl.user', 'plu')->addSelect('plu')
            ->leftJoin('p.comments', 'c')->addSelect('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->leftJoin('c.likes', 'cl')->addSelect('cl')
            ->leftJoin('cl.user', 'clu')->addSelect('clu')
            ->leftJoin('c.replies', 'r')->addSelect('r')
            ->leftJoin('r.author', 'ra')->addSelect('ra')
            ->leftJoin('r.likes', 'rl')->addSelect('rl')
            ->leftJoin('rl.user', 'rlu')->addSelect('rlu')
            ->leftJoin('r.replies', 'rr')->addSelect('rr')
            ->leftJoin('rr.author', 'rra')->addSelect('rra')
            ->leftJoin('rr.likes', 'rrl')->addSelect('rrl')
            ->leftJoin('rrl.user', 'rrlu')->addSelect('rrlu')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $postIds)
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->addOrderBy('r.createdAt', 'ASC')
            ->addOrderBy('rr.createdAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAccessibleForUser(string $id, User $user): ?Post
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('post.id = :id')
            ->andWhere('post.visibility = :publicVisibility OR author.id = :viewerId')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('publicVisibility', Post::VISIBILITY_PUBLIC)
            ->setParameter('viewerId', $user->getId(), UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Post>
     */
    public function findProfileFeedForUser(User $profileUser, User $viewer, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->leftJoin('p.likes', 'pl')->addSelect('pl')
            ->leftJoin('pl.user', 'plu')->addSelect('plu')
            ->leftJoin('p.comments', 'c')->addSelect('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->leftJoin('c.likes', 'cl')->addSelect('cl')
            ->leftJoin('cl.user', 'clu')->addSelect('clu')
            ->leftJoin('c.replies', 'r')->addSelect('r')
            ->leftJoin('r.author', 'ra')->addSelect('ra')
            ->leftJoin('r.likes', 'rl')->addSelect('rl')
            ->leftJoin('rl.user', 'rlu')->addSelect('rlu')
            ->leftJoin('r.replies', 'rr')->addSelect('rr')
            ->leftJoin('rr.author', 'rra')->addSelect('rra')
            ->leftJoin('rr.likes', 'rrl')->addSelect('rrl')
            ->leftJoin('rrl.user', 'rrlu')->addSelect('rrlu')
            ->where('p.author = :profileUser')
            ->andWhere('p.visibility = :public OR p.author = :viewer')
            ->setParameter('profileUser', $profileUser)
            ->setParameter('viewer', $viewer)
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->addOrderBy('r.createdAt', 'ASC')
            ->addOrderBy('rr.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{postsCount:int, publicPostsCount:int, privatePostsCount:int, likesReceivedCount:int, commentsReceivedCount:int}
     */
    public function buildProfileStatsForViewer(User $profileUser, User $viewer): array
    {
        $isOwner = $profileUser->getId()->equals($viewer->getId());

        $visibilityClause = $isOwner
            ? 'p.author = :profileUser'
            : 'p.author = :profileUser AND p.visibility = :public';

        $postCountsQb = $this->createQueryBuilder('p')
            ->select('p.visibility AS visibility, COUNT(p.id) AS count')
            ->where($visibilityClause)
            ->setParameter('profileUser', $profileUser)
            ->groupBy('p.visibility');
        if (!$isOwner) {
            $postCountsQb->setParameter('public', Post::VISIBILITY_PUBLIC);
        }
        $postCounts = $postCountsQb->getQuery()->getScalarResult();

        $publicPostsCount = 0;
        $privatePostsCount = 0;

        foreach ($postCounts as $row) {
            $visibility = (string) ($row['visibility'] ?? '');
            $count = (int) ($row['count'] ?? 0);
            if ($visibility === Post::VISIBILITY_PUBLIC) {
                $publicPostsCount = $count;
                continue;
            }

            if ($visibility === Post::VISIBILITY_PRIVATE) {
                $privatePostsCount = $count;
            }
        }

        $likesQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(pl.id)')
            ->from(PostLikeEntity::class, 'pl')
            ->innerJoin('pl.post', 'p')
            ->where($visibilityClause)
            ->setParameter('profileUser', $profileUser);
        if (!$isOwner) {
            $likesQb->setParameter('public', Post::VISIBILITY_PUBLIC);
        }

        $commentsQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(CommentEntity::class, 'c')
            ->innerJoin('c.post', 'p')
            ->where($visibilityClause)
            ->setParameter('profileUser', $profileUser);
        if (!$isOwner) {
            $commentsQb->setParameter('public', Post::VISIBILITY_PUBLIC);
        }

        return [
            'postsCount' => $publicPostsCount + $privatePostsCount,
            'publicPostsCount' => $publicPostsCount,
            'privatePostsCount' => $privatePostsCount,
            'likesReceivedCount' => (int) $likesQb->getQuery()->getSingleScalarResult(),
            'commentsReceivedCount' => (int) $commentsQb->getQuery()->getSingleScalarResult(),
        ];
    }
}
