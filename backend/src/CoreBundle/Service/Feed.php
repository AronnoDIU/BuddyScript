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

class Feed
{
    private readonly EntityManagerInterface $entityManager;

    private readonly ApiFormatter $formatter;

    private readonly string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        ApiFormatter $formatter,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->projectDir = $projectDir;
    }

    /**
     * @return array<string,mixed>
     */
    public function feed(User $user, string $query): array
    {
        $normalizedQuery = trim($query);
        $posts = $this->getPostRepository()->findFeedForUser($user, 50, $normalizedQuery === '' ? null : $normalizedQuery);

        return [
            'posts' => array_map(fn (Post $post): array => $this->formatter->post($post, $user), $posts),
            'query' => $normalizedQuery,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function createPost(User $user, string $content, string $visibility, ?UploadedFile $image): array
    {
        $post = new Post();
        $post
            ->setAuthor($user)
            ->setContent(trim($content))
            ->setVisibility($visibility);

        if ($image instanceof UploadedFile) {
            $path = $this->storePostImage($image);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid image upload.');
            }

            $post->setImagePath($path);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return ['post' => $this->formatter->post($post, $user)];
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

        return ['comment' => $this->formatter->comment($comment, $user)];
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
        } catch (\JsonException) {
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
}
