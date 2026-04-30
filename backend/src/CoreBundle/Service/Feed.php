<?php

namespace CoreBundle\Service;

use CoreBundle\Entity\Comment;
use CoreBundle\Entity\Comment\Like as CommentLike;
use CoreBundle\Entity\Post;
use CoreBundle\Entity\Post\Like as PostLike;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Comment\LikeRepository as CommentLikeRepository;
use CoreBundle\Repository\CommentRepository;
use CoreBundle\Repository\Post\LikeRepository as PostLikeRepository;
use CoreBundle\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class Feed
{
    private EntityManagerInterface $entityManager;

    private ApiFormatter $formatter;

    private string $projectDir;

    private Analytics $analytics;

    private SocialGraph $socialGraph;

    private TagAwareCacheInterface $cacheService; // Inject Cache service

    public function __construct(
        EntityManagerInterface $entityManager,
        ApiFormatter $formatter,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        Analytics $analytics,
        SocialGraph $socialGraph,
        TagAwareCacheInterface $cacheService,
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->projectDir = $projectDir;
        $this->analytics = $analytics;
        $this->socialGraph = $socialGraph;
        $this->cacheService = $cacheService;
    }

    /**
     * @return array<string,mixed>
     */
    public function feed(User $user, string $query, int $limit = 20, int $offset = 0): array
    {
        $normalizedQuery = trim($query);
        $safeLimit = max(5, min(50, $limit));
        $safeOffset = max(0, $offset);

        $cacheKey = sprintf('feed_user_%s_query_%s_limit_%d_offset_%d',
            $user->getId()->toRfc4122(),
            md5($normalizedQuery),
            $safeLimit,
            $safeOffset
        );

        // Try to retrieve from cache
        $cachedFeed = $this->cacheService->getItem($cacheKey);
        if ($cachedFeed->isHit()) {
            $this->analytics->trackUserActivity($user, 'view_feed_cached', ['query' => $normalizedQuery]);
            return $cachedFeed->get();
        }

        // We fetch more than needed to allow for ranking re-ordering
        $fetchLimit = $safeLimit * 2;

        $posts = $this->getPostRepository()->findFeedForUser(
            $user,
            $fetchLimit,
            $normalizedQuery === '' ? null : $normalizedQuery,
            $safeOffset,
        );

        $rankedPosts = $this->rankPosts($posts, $user);

        $hasMore = count($rankedPosts) > $safeLimit;
        $pagePosts = array_slice($rankedPosts, 0, $safeLimit);

        $feedData = [
            'posts' => array_map(fn (Post $post): array => $this->formatter->post($post, $user), $pagePosts),
            'query' => $normalizedQuery,
            'pagination' => [
                'limit' => $safeLimit,
                'offset' => $safeOffset,
                'nextOffset' => $safeOffset + count($pagePosts),
                'hasMore' => $hasMore,
            ],
        ];

        // Cache the result
        $cachedFeed->set($feedData);
        $cachedFeed->expiresAfter(300); // Cache for 5 minutes
        $cachedFeed->tag(['feed', 'user_feed_' . $user->getId()->toRfc4122()]); // Tag for invalidation
        $this->cacheService->save($cachedFeed);

        $this->analytics->trackUserActivity($user, 'view_feed', ['query' => $normalizedQuery]);

        return $feedData;
    }

    /**
     * @param list<Post> $posts
     * @return list<Post>
     */
    private function rankPosts(array $posts, User $viewer): array
    {
        if ($posts === []) {
            return [];
        }

        $followingIds = array_map(
            fn(User $u) => $u->getId()->toRfc4122(),
            $this->socialGraph->getFollowing($viewer)
        );

        $scoredPosts = [];
        $now = new \DateTimeImmutable();

        foreach ($posts as $post) {
            $score = 1.0;

            // Recency: newer posts get higher scores (exponential decay)
            $ageInHours = ($now->getTimestamp() - $post->getCreatedAt()->getTimestamp()) / 3600;
            $recencyScore = exp(-$ageInHours / 48); // Decay over 48 hours
            $score *= (1 + $recencyScore);

            // Engagement: likes and comments boost score
            $engagement = count($post->getLikes()) * 2 + count($post->getComments()) * 5;
            $score *= (1 + log1p($engagement));

            // Social Relevance (Affinity): posts from followed users get a significant boost
            if (in_array($post->getAuthor()->getId()->toRfc4122(), $followingIds, true)) {
                $score *= 2.0; // Increased boost for followed users
            }

            // Media Boost: posts with images get a boost
            if ($post->getImagePath() !== null) {
                $score *= 1.3; // 30% boost for posts with images
            }

            // Placeholder for more advanced affinity based on interaction history
            // if ($this->hasUserInteractedWithPost($viewer, $post)) {
            //     $score *= 1.1;
            // }

            $scoredPosts[] = [
                'post' => $post,
                'score' => $score
            ];
        }

        usort($scoredPosts, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn($item) => $item['post'], $scoredPosts);
    }

    /**
     * @return array<string,mixed>
     */
    public function createPost(User $user, string $content, string $visibility, ?UploadedFile $image): array
    {
        $normalizedContent = trim($content);
        $indexed = $this->extractDiscoveryTokens($normalizedContent);

        $post = new Post();
        $post
            ->setAuthor($user)
            ->setContent($normalizedContent)
            ->setVisibility($visibility)
            ->setHashtags($indexed['hashtags'])
            ->setTopics($indexed['topics']);

        if ($image instanceof UploadedFile) {
            $path = $this->storePostImage($image);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid image upload.');
            }

            $post->setImagePath($path);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        // Invalidate feed cache for the user and general feed
        $this->cacheService->clearByTag('feed');
        $this->cacheService->clearByTag('user_feed_' . $user->getId()->toRfc4122());


        $this->analytics->trackUserActivity($user, 'create_post', ['post_id' => $post->getId()->toRfc4122()]);

        return ['post' => $this->formatter->post($post, $user)];
    }

    /**
     * @return array<string,mixed>
     */
    public function discovery(User $viewer, int $limit = 12): array
    {
        $safeLimit = max(3, min(24, $limit));

        $stories = $this->getPostRepository()->findDiscoveryStories($safeLimit);
        $reels = $this->getPostRepository()->findDiscoveryReels($safeLimit);
        $live = $this->getPostRepository()->findDiscoveryLive($safeLimit);

        $this->analytics->trackUserActivity($viewer, 'view_discovery');

        return [
            'stories' => array_map(fn (Post $post): array => $this->formatter->discoveryCard($post, $viewer, 'story'), $stories),
            'reels' => array_map(fn (Post $post): array => $this->formatter->discoveryCard($post, $viewer, 'reel'), $reels),
            'live' => array_map(fn (Post $post): array => $this->formatter->discoveryCard($post, $viewer, 'live'), $live),
            'trendingTopics' => $this->trendingTopics(12)['topics'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function discoverySearch(User $viewer, string $query, int $limit = 20): array
    {
        $normalized = trim($query);
        if ($normalized === '') {
            return [
                'query' => '',
                'users' => [],
                'posts' => [],
                'hashtags' => [],
            ];
        }

        $safeLimit = max(5, min(40, $limit));
        $posts = $this->getPostRepository()->searchPublicPosts($normalized, $safeLimit);
        $users = $this->getPostRepository()->searchPublicAuthors($normalized, $safeLimit);
        $hashtags = $this->getPostRepository()->searchHashtags($normalized, 15);

        $this->analytics->trackUserActivity($viewer, 'discovery_search', ['query' => $normalized]);

        return [
            'query' => $normalized,
            'users' => array_map(fn (User $user): array => $this->formatter->user($user), $users),
            'posts' => array_map(fn (Post $post): array => $this->formatter->post($post, $viewer), $posts),
            'hashtags' => $hashtags,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function trendingTopics(int $limit = 12): array
    {
        $safeLimit = max(5, min(50, $limit));
        $tokens = [];

        foreach ($this->getPostRepository()->findRecentPublicPostsForIndexing(250) as $post) {
            foreach ($post->getHashtags() as $hashtag) {
                $key = '#' . $hashtag;
                $tokens[$key] = ($tokens[$key] ?? 0) + 3;
            }

            foreach ($post->getTopics() as $topic) {
                $tokens[$topic] = ($tokens[$topic] ?? 0) + 1;
            }
        }

        arsort($tokens);
        $topicRows = [];
        foreach (array_slice($tokens, 0, $safeLimit, true) as $topic => $score) {
            $topicRows[] = [
                'topic' => $topic,
                'score' => $score,
            ];
        }

        return ['topics' => $topicRows];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function addComment(User $user, string $postId, string $content): ?array
    {
        $post = $this->getPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof Post) {
            return null;
        }

        $comment = new Comment();
        $comment
            ->setPost($post)
            ->setAuthor($user)
            ->setContent(trim($content));

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        // Invalidate feed cache for the user and general feed
        $this->cacheService->clearByTag('feed');
        $this->cacheService->clearByTag('user_feed_' . $user->getId()->toRfc4122());

        $this->analytics->trackUserActivity($user, 'add_comment', ['post_id' => $postId]);

        return ['comment' => $this->formatter->comment($comment, $user)];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function deletePost(User $user, string $postId): ?array
    {
        $post = $this->getPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof Post) {
            return null;
        }

        if (!$post->getAuthor()->getId()->equals($user->getId())) {
            return null;
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        // Invalidate feed cache for the user and general feed
        $this->cacheService->clearByTag('feed');
        $this->cacheService->clearByTag('user_feed_' . $user->getId()->toRfc4122());

        return ['message' => 'Post deleted successfully.'];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function addReply(User $user, string $commentId, string $content): ?array
    {
        $parent = $this->getCommentRepository()->findAccessibleForUser($commentId, $user);
        if (!$parent instanceof Comment) {
            return null;
        }

        $reply = new Comment();
        $reply
            ->setPost($parent->getPost())
            ->setParent($parent)
            ->setAuthor($user)
            ->setContent(trim($content));

        $this->entityManager->persist($reply);
        $this->entityManager->flush();

        // Invalidate feed cache for the user and general feed
        $this->cacheService->clearByTag('feed');
        $this->cacheService->clearByTag('user_feed_' . $user->getId()->toRfc4122());

        $this->analytics->trackUserActivity($user, 'add_reply', ['comment_id' => $commentId]);

        return ['reply' => $this->formatter->comment($reply, $user)];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function togglePostLike(User $user, string $postId): ?array
    {
        $post = $this->getPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof Post) {
            return null;
        }

        $like = $this->getPostLikeRepository()->findOneByPostAndUser($post, $user);
        $liked = false;

        if ($like instanceof PostLike) {
            $this->entityManager->remove($like);
        } else {
            $like = new PostLike();
            $like->setPost($post)->setUser($user);
            $this->entityManager->persist($like);
            $liked = true;
        }

        $this->entityManager->flush();

        // Invalidate feed cache for the user and general feed
        $this->cacheService->clearByTag('feed');
        $this->cacheService->clearByTag('user_feed_' . $user->getId()->toRfc4122());

        if ($liked) {
            $this->analytics->trackUserActivity($user, 'like_post', ['post_id' => $postId]);
        }

        $likes = $this->getPostLikeRepository()->findBy(['post' => $post], ['createdAt' => 'DESC']);

        return [
            'liked' => $liked,
            'likes' => array_map(fn (PostLike $postLike): array => $this->formatter->user($postLike->getUser()), $likes),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function toggleCommentLike(User $user, string $commentId): ?array
    {
        $comment = $this->getCommentRepository()->findAccessibleForUser($commentId, $user);
        if (!$comment instanceof Comment) {
            return null;
        }

        $like = $this->getCommentLikeRepository()->findOneByCommentAndUser($comment, $user);
        $liked = false;

        if ($like instanceof CommentLike) {
            $this->entityManager->remove($like);
        } else {
            $like = new CommentLike();
            $like->setComment($comment)->setUser($user);
            $this->entityManager->persist($like);
            $liked = true;
        }

        $this->entityManager->flush();

        // Invalidate feed cache for the user and general feed
        $this->cacheService->clearByTag('feed');
        $this->cacheService->clearByTag('user_feed_' . $user->getId()->toRfc4122());

        if ($liked) {
            $this->analytics->trackUserActivity($user, 'like_comment', ['comment_id' => $commentId]);
        }

        $likes = $this->getCommentLikeRepository()->findBy(['comment' => $comment], ['createdAt' => 'DESC']);

        return [
            'liked' => $liked,
            'likes' => array_map(fn (CommentLike $commentLike): array => $this->formatter->user($commentLike->getUser()), $likes),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function postLikes(User $user, string $postId): ?array
    {
        $post = $this->getPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof Post) {
            return null;
        }

        return [
            'likes' => array_map(
                fn (PostLike $postLike): array => $this->formatter->user($postLike->getUser()),
                $this->getPostLikeRepository()->findBy(['post' => $post], ['createdAt' => 'DESC'])
            ),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function commentLikes(User $user, string $commentId): ?array
    {
        $comment = $this->getCommentRepository()->findAccessibleForUser($commentId, $user);
        if (!$comment instanceof Comment) {
            return null;
        }

        return [
            'likes' => array_map(
                fn (CommentLike $commentLike): array => $this->formatter->user($commentLike->getUser()),
                $this->getCommentLikeRepository()->findBy(['comment' => $comment], ['createdAt' => 'DESC'])
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function extractJsonPayload(Request $request): array
    {
        try {
            $decoded = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid JSON.');
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function storePostImage(UploadedFile $file): ?string
    {
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false || !isset($imageInfo[2])) {
            return null;
        }

        $extensionMap = [
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        $extension = $extensionMap[$imageInfo[2]] ?? null;
        if ($extension === null) {
            return null;
        }

        if ($file->getSize() !== null && $file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $uploadDir = $this->projectDir . '/public/uploads/posts';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }

        $name = Uuid::v7()->toRfc4122() . '.' . $extension;
        $file->move($uploadDir, $name);

        return '/uploads/posts/' . $name;
    }

    private function getPostRepository(): PostRepository
    {
        $repository = $this->entityManager->getRepository(Post::class);
        if (!$repository instanceof PostRepository) {
            throw new \LogicException('Post repository is not configured correctly.');
        }

        return $repository;
    }

    private function getCommentRepository(): CommentRepository
    {
        $repository = $this->entityManager->getRepository(Comment::class);
        if (!$repository instanceof CommentRepository) {
            throw new \LogicException('Comment repository is not configured correctly.');
        }

        return $repository;
    }

    private function getPostLikeRepository(): PostLikeRepository
    {
        $repository = $this->entityManager->getRepository(PostLike::class);
        if (!$repository instanceof PostLikeRepository) {
            throw new \LogicException('Post like repository is not configured correctly.');
        }

        return $repository;
    }

    private function getCommentLikeRepository(): CommentLikeRepository
    {
        $repository = $this->entityManager->getRepository(CommentLike::class);
        if (!$repository instanceof CommentLikeRepository) {
            throw new \LogicException('Comment like repository is not configured correctly.');
        }

        return $repository;
    }

    /**
     * @return array{hashtags:list<string>,topics:list<string>}
     */
    private function extractDiscoveryTokens(string $content): array
    {
        preg_match_all('/#([\p{L}\p{N}_]{2,50})/u', $content, $hashtagMatches);
        $hashtags = array_map(
                static fn(string $tag): string => mb_strtolower($tag),
                $hashtagMatches[1] ?? []
            )
                |> array_unique(...)
                |> array_values(...);

        $normalized = preg_replace('/#[\p{L}\p{N}_]+/u', ' ', mb_strtolower($content));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', (string) $normalized);
        $parts = preg_split('/\s+/u', trim((string) $normalized)) ?: [];

        $stopWords = [
            'the', 'and', 'for', 'are', 'this', 'that', 'with', 'from', 'your', 'you', 'have', 'has', 'was', 'were',
            'will', 'would', 'about', 'into', 'than', 'then', 'they', 'them', 'just', 'like', 'post', 'today',
        ];

        $topics = [];
        foreach ($parts as $part) {
            if (mb_strlen($part) < 3 || in_array($part, $stopWords, true)) {
                continue;
            }

            $topics[] = $part;
            if (count($topics) >= 24) {
                break;
            }
        }

        return [
            'hashtags' => $hashtags,
            'topics' => array_values(array_unique($topics)),
        ];
    }
}
