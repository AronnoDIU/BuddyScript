<?php

declare(strict_types=1);

namespace CoreBundle\Service\Community;

use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupPost;
use CoreBundle\Entity\Community\GroupPostComment;
use CoreBundle\Entity\Community\GroupPostCommentLike;
use CoreBundle\Entity\Community\GroupPostLike;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Community\GroupPostCommentLikeRepository;
use CoreBundle\Repository\Community\GroupPostCommentRepository;
use CoreBundle\Repository\Community\GroupPostLikeRepository;
use CoreBundle\Repository\Community\GroupPostRepository;
use CoreBundle\Repository\Community\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class GroupPostService
{
    private readonly EntityManagerInterface $entityManager;
    private readonly string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ) {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function createPost(User $author, string $groupId, string $content, ?UploadedFile $image): ?array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($groupId, $author);
        if (!$group instanceof Group) {
            return null;
        }

        if (!$group->hasPermission($author, 'post')) {
            throw new \InvalidArgumentException('Insufficient permissions to post in this group.');
        }

        $post = new GroupPost();
        $post
            ->setGroup($group)
            ->setAuthor($author)
            ->setContent($content)
            ->setHashtags($this->extractHashtags($content));

        if ($image instanceof UploadedFile) {
            $path = $this->storePostImage($image);
            if ($path === null) {
                throw new \InvalidArgumentException('Invalid image upload.');
            }
            $post->setImagePath($path);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return ['post' => $this->formatPost($post, $author)];
    }

    /**
     * @return list<GroupPost>
     */
    public function getPosts(User $viewer, string $groupId, int $limit = 20): array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($groupId, $viewer);
        if (!$group instanceof Group) {
            throw new \InvalidArgumentException('Group not found or inaccessible.');
        }

        return $this->getGroupPostRepository()->findByGroup($group, $viewer, $limit);
    }

    /**
     * @return list<GroupPost>
     */
    public function searchPosts(User $viewer, string $groupId, string $query, int $limit = 20): array
    {
        $group = $this->getGroupRepository()->findAccessibleForUser($groupId, $viewer);
        if (!$group instanceof Group) {
            throw new \InvalidArgumentException('Group not found or inaccessible.');
        }

        return $this->getGroupPostRepository()->searchInGroup($group, $viewer, $query, $limit);
    }

    public function getAccessiblePost(string $id, User $user): ?GroupPost
    {
        return $this->getGroupPostRepository()->findAccessibleForUser($id, $user);
    }

    /** @return array<string,mixed>|null */
    public function deletePost(User $user, string $postId): ?array
    {
        $post = $this->getGroupPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof GroupPost) {
            return null;
        }

        $isAuthor = $post->getAuthor()->getId()->equals($user->getId());
        if (!$isAuthor) {
            return null;
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return ['message' => 'Post deleted successfully.'];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function toggleLike(User $user, string $postId): ?array
    {
        $post = $this->getGroupPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof GroupPost) {
            return null;
        }

        $like = $this->getGroupPostLikeRepository()->findByPostAndUser($post, $user);
        $liked = false;

        if ($like instanceof GroupPostLike) {
            $this->entityManager->remove($like);
        } else {
            $like = new GroupPostLike();
            $like->setPost($post)->setUser($user);
            $this->entityManager->persist($like);
            $liked = true;
        }

        $this->entityManager->flush();

        $likes = $this->getGroupPostLikeRepository()->findByPost($post, 50);

        return [
            'liked' => $liked,
            'likes' => array_map(fn ($like) => [
                'id' => $like->getUser()->getId()->toRfc4122(),
                'username' => $like->getUser()->getUsername(),
                'displayName' => $like->getUser()->getFirstName() . ' ' . $like->getUser()->getLastName(),
            ], $likes),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function addComment(User $user, string $postId, string $content): ?array
    {
        $post = $this->getGroupPostRepository()->findAccessibleForUser($postId, $user);
        if (!$post instanceof GroupPost) {
            return null;
        }

        $comment = new GroupPostComment();
        $comment
            ->setPost($post)
            ->setAuthor($user)
            ->setContent($content);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return ['comment' => $this->formatComment($comment, $user)];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function toggleCommentLike(User $user, string $commentId): ?array
    {
        $comment = $this->getGroupPostCommentRepository()->findAccessibleForUser($commentId, $user);
        if (!$comment instanceof GroupPostComment) {
            return null;
        }

        $like = $this->getGroupPostCommentLikeRepository()->findByCommentAndUser($comment, $user);
        $liked = false;

        if ($like instanceof GroupPostCommentLike) {
            $this->entityManager->remove($like);
        } else {
            $like = new GroupPostCommentLike();
            $like->setComment($comment)->setUser($user);
            $this->entityManager->persist($like);
            $liked = true;
        }

        $this->entityManager->flush();

        $likes = $this->getGroupPostCommentLikeRepository()->findByComment($comment, 50);

        return [
            'liked' => $liked,
            'likes' => array_map(fn ($like) => [
                'id' => $like->getUser()->getId()->toRfc4122(),
                'username' => $like->getUser()->getUsername(),
                'displayName' => $like->getUser()->getFirstName() . ' ' . $like->getUser()->getLastName(),
            ], $likes),
        ];
    }

    /**
     * @return list<string>
     */
    private function extractHashtags(string $content): array
    {
        preg_match_all('/#([\p{L}\p{N}_]{2,50})/u', $content, $matches);
        return array_values(array_unique(array_map(
            static fn (string $tag): string => mb_strtolower($tag),
            $matches[1] ?? []
        )));
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

        $uploadDir = $this->projectDir . '/public/uploads/group-posts';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }

        $name = Uuid::v7()->toRfc4122() . '.' . $extension;
        $file->move($uploadDir, $name);

        return '/uploads/group-posts/' . $name;
    }

    /**
     * @return array<string,mixed>
     */
    private function formatPost(GroupPost $post, User $viewer): array
    {
        $likes = [];
        $likedByMe = false;
        foreach ($post->getLikes() as $like) {
            $likes[] = [
                'id' => $like->getUser()->getId()->toRfc4122(),
                'username' => $like->getUser()->getUsername(),
                'displayName' => $like->getUser()->getFirstName() . ' ' . $like->getUser()->getLastName(),
            ];
            if ($like->getUser()->getId()->equals($viewer->getId())) {
                $likedByMe = true;
            }
        }

        $comments = [];
        foreach ($post->getComments() as $comment) {
            if ($comment->getParent() !== null) {
                continue;
            }
            $comments[] = $this->formatComment($comment, $viewer);
        }

        return [
            'id' => $post->getId()->toRfc4122(),
            'content' => $post->getContent(),
            'imageUrl' => $post->getImagePath(),
            'hashtags' => $post->getHashtags(),
            'createdAt' => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $post->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'author' => [
                'id' => $post->getAuthor()->getId()->toRfc4122(),
                'username' => $post->getAuthor()->getUsername(),
                'displayName' => $post->getAuthor()->getFirstName() . ' ' . $post->getAuthor()->getLastName(),
            ],
            'group' => [
                'id' => $post->getGroup()->getId()->toRfc4122(),
                'name' => $post->getGroup()->getName(),
            ],
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'comments' => $comments,
            'commentsCount' => $post->getCommentCount(),
            'canDelete' => $post->getAuthor()->getId()->equals($viewer->getId()),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function formatComment(GroupPostComment $comment, User $viewer): array
    {
        $likes = [];
        $likedByMe = false;
        foreach ($comment->getLikes() as $like) {
            $likes[] = [
                'id' => $like->getUser()->getId()->toRfc4122(),
                'username' => $like->getUser()->getUsername(),
                'displayName' => $like->getUser()->getFirstName() . ' ' . $like->getUser()->getLastName(),
            ];
            if ($like->getUser()->getId()->equals($viewer->getId())) {
                $likedByMe = true;
            }
        }

        $replies = [];
        foreach ($comment->getReplies() as $reply) {
            $replies[] = $this->formatComment($reply, $viewer);
        }

        return [
            'id' => $comment->getId()->toRfc4122(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $comment->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'author' => [
                'id' => $comment->getAuthor()->getId()->toRfc4122(),
                'username' => $comment->getAuthor()->getUsername(),
                'displayName' => $comment->getAuthor()->getFirstName() . ' ' . $comment->getAuthor()->getLastName(),
            ],
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'replies' => $replies,
            'repliesCount' => $comment->getReplyCount(),
            'isReply' => $comment->isReply(),
        ];
    }

    private function getGroupRepository(): GroupRepository
    {
        $repository = $this->entityManager->getRepository(Group::class);
        if (!$repository instanceof GroupRepository) {
            throw new \LogicException('Group repository is not configured correctly.');
        }
        return $repository;
    }

    private function getGroupPostRepository(): GroupPostRepository
    {
        $repository = $this->entityManager->getRepository(GroupPost::class);
        if (!$repository instanceof GroupPostRepository) {
            throw new \LogicException('GroupPost repository is not configured correctly.');
        }
        return $repository;
    }

    private function getGroupPostLikeRepository(): GroupPostLikeRepository
    {
        $repository = $this->entityManager->getRepository(GroupPostLike::class);
        if (!$repository instanceof GroupPostLikeRepository) {
            throw new \LogicException('GroupPostLike repository is not configured correctly.');
        }
        return $repository;
    }

    private function getGroupPostCommentRepository(): GroupPostCommentRepository
    {
        $repository = $this->entityManager->getRepository(GroupPostComment::class);
        if (!$repository instanceof GroupPostCommentRepository) {
            throw new \LogicException('GroupPostComment repository is not configured correctly.');
        }
        return $repository;
    }

    private function getGroupPostCommentLikeRepository(): GroupPostCommentLikeRepository
    {
        $repository = $this->entityManager->getRepository(GroupPostCommentLike::class);
        if (!$repository instanceof GroupPostCommentLikeRepository) {
            throw new \LogicException('GroupPostCommentLike repository is not configured correctly.');
        }
        return $repository;
    }
}
