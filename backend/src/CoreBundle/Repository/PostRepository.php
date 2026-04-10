<?php

declare(strict_types=1);

namespace CoreBundle\Repository;

use CoreBundle\Entity\Post;
use CoreBundle\Entity\Post\Like as PostLikeEntity;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    public function findFeedForUser(User $viewer, int $limit = 50, ?string $query = null): array
    {
        $query = $query !== null ? trim($query) : null;

        $commentsByViewerDql = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(CommentEntity::class, 'vc')
            ->where('vc.post = p')
            ->andWhere('vc.author = :viewer')
            ->getDQL();

        $postSeedQb = $this->createQueryBuilder('p')
            ->select('p')
            ->innerJoin('p.author', 'a')
            ->where('p.visibility = :public OR p.author = :viewer OR EXISTS(' . $commentsByViewerDql . ')')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('viewer', $viewer)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($query !== null && $query !== '') {
            $search = '%' . mb_strtolower($query) . '%';
            $postSeedQb
                ->andWhere('LOWER(p.content) LIKE :search OR LOWER(a.email) LIKE :search OR LOWER(CONCAT(CONCAT(a.firstName, :space), a.lastName)) LIKE :search')
                ->setParameter('search', $search)
                ->setParameter('space', ' ');
        }

        $seedPosts = $postSeedQb->getQuery()->getResult();
        if ($seedPosts === []) {
            return [];
        }

        $seedPostIds = array_map(static fn (Post $post): Uuid => $post->getId(), $seedPosts);

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
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->addOrderBy('r.createdAt', 'ASC')
            ->addOrderBy('rr.createdAt', 'ASC');

        $this->applyUuidIdFilter($qb, 'p.id', $seedPostIds);

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

    /** @return list<Post> */
    public function findDiscoveryStories(int $limit = 12): array
    {
        $safeLimit = max(3, min(24, $limit));
        $since = (new \DateTimeImmutable('-2 days'));

        $posts = $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'a')->addSelect('a')
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
            ->where('p.visibility = :public')
            ->andWhere('p.imagePath IS NOT NULL')
            ->andWhere('p.createdAt >= :since')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('since', $since)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(150)
            ->getQuery()
            ->getResult();

        $seenAuthors = [];
        $picked = [];
        foreach ($posts as $post) {
            if (!$post instanceof Post) {
                continue;
            }

            $authorId = $post->getAuthor()->getId()->toRfc4122();
            if (isset($seenAuthors[$authorId])) {
                continue;
            }

            $seenAuthors[$authorId] = true;
            $picked[] = $post;
            if (count($picked) >= $safeLimit) {
                break;
            }
        }

        return $picked;
    }

    /** @return list<Post> */
    public function findDiscoveryReels(int $limit = 12): array
    {
        $safeLimit = max(3, min(24, $limit));

        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'a')->addSelect('a')
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
            ->where('p.visibility = :public')
            ->andWhere('p.imagePath IS NOT NULL')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->orderBy('SIZE(p.likes)', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($safeLimit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Post> */
    public function findDiscoveryLive(int $limit = 12): array
    {
        $safeLimit = max(3, min(24, $limit));
        $since = new \DateTimeImmutable('-90 minutes');

        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'a')->addSelect('a')
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
            ->where('p.visibility = :public')
            ->andWhere('p.createdAt >= :since')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('since', $since)
            ->orderBy('SIZE(p.comments)', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($safeLimit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Post> */
    public function searchPublicPosts(string $query, int $limit = 20): array
    {
        $safeLimit = max(5, min(40, $limit));
        $search = '%' . mb_strtolower(trim($query)) . '%';

        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'a')->addSelect('a')
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
            ->where('p.visibility = :public')
            ->andWhere('LOWER(p.content) LIKE :search OR LOWER(CONCAT(CONCAT(a.firstName, :space), a.lastName)) LIKE :search OR LOWER(a.email) LIKE :search')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('search', $search)
            ->setParameter('space', ' ')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($safeLimit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<User> */
    public function searchPublicAuthors(string $query, int $limit = 20): array
    {
        $safeLimit = max(5, min(40, $limit));
        $search = '%' . mb_strtolower(trim($query)) . '%';

        return $this->createQueryBuilder('p')
            ->select('DISTINCT a')
            ->innerJoin('p.author', 'a')
            ->where('p.visibility = :public')
            ->andWhere('LOWER(CONCAT(CONCAT(a.firstName, :space), a.lastName)) LIKE :search OR LOWER(a.email) LIKE :search')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('space', ' ')
            ->setParameter('search', $search)
            ->setMaxResults($safeLimit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<string> */
    public function searchHashtags(string $query, int $limit = 15): array
    {
        $needle = mb_strtolower(ltrim(trim($query), '#'));
        if ($needle === '') {
            return [];
        }

        $hashtags = [];
        foreach ($this->findRecentPublicPostsForIndexing(250) as $post) {
            foreach ($post->getHashtags() as $hashtag) {
                if (str_contains($hashtag, $needle)) {
                    $hashtags['#' . $hashtag] = true;
                }
            }
        }

        return array_slice(array_keys($hashtags), 0, max(5, min(30, $limit)));
    }

    /** @return list<Post> */
    public function findRecentPublicPostsForIndexing(int $limit = 250): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.visibility = :public')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(max(20, min(500, $limit)))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Post>
     */
    public function findProfileFeedForUser(User $profileUser, User $viewer, int $limit = 20): array
    {
        $isOwner = $profileUser->getId()->equals($viewer->getId());

        $seedQb = $this->createQueryBuilder('p')
            ->select('p')
            ->where('IDENTITY(p.author) = :profileUserId')
            ->setParameter('profileUserId', $profileUser->getId(), UuidType::NAME)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        if (!$isOwner) {
            $seedQb
                ->andWhere('p.visibility = :public')
                ->setParameter('public', Post::VISIBILITY_PUBLIC);
        }

        $seedPosts = $seedQb->getQuery()->getResult();
        if ($seedPosts === []) {
            return [];
        }

        $seedPostIds = array_map(static fn (Post $post): Uuid => $post->getId(), $seedPosts);

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
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->addOrderBy('r.createdAt', 'ASC')
            ->addOrderBy('rr.createdAt', 'ASC');

        $this->applyUuidIdFilter($qb, 'p.id', $seedPostIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{postsCount:int, publicPostsCount:int, privatePostsCount:int, likesReceivedCount:int, commentsReceivedCount:int}
     */
    public function buildProfileStatsForViewer(User $profileUser, User $viewer): array
    {
        $isOwner = $profileUser->getId()->equals($viewer->getId());

        $visibilityClause = $isOwner
            ? 'IDENTITY(p.author) = :profileUserId'
            : 'IDENTITY(p.author) = :profileUserId AND p.visibility = :public';

        $postCountsQb = $this->createQueryBuilder('p')
            ->select('p.visibility AS visibility, COUNT(p.id) AS count')
            ->where($visibilityClause)
            ->setParameter('profileUserId', $profileUser->getId(), UuidType::NAME)
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
            ->setParameter('profileUserId', $profileUser->getId(), UuidType::NAME);
        if (!$isOwner) {
            $likesQb->setParameter('public', Post::VISIBILITY_PUBLIC);
        }

        $commentsQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(CommentEntity::class, 'c')
            ->innerJoin('c.post', 'p')
            ->where($visibilityClause)
            ->setParameter('profileUserId', $profileUser->getId(), UuidType::NAME);
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

    /**
     * @param list<Uuid> $ids
     */
    private function applyUuidIdFilter(QueryBuilder $qb, string $field, array $ids): void
    {
        if ($ids === []) {
            $qb->andWhere('1 = 0');

            return;
        }

        $orX = $qb->expr()->orX();
        foreach ($ids as $index => $id) {
            $param = 'uuid_' . $index;
            $orX->add($qb->expr()->eq($field, ':' . $param));
            $qb->setParameter($param, $id, UuidType::NAME);
        }

        $qb->andWhere($orX);
    }
}
