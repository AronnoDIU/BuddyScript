<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Comment;
use CoreBundle\Entity\Post;
use CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class ApiFormatter
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function user(User $user): array
    {
        return [
            'id' => $user->getId()->toRfc4122(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'displayName' => $user->getDisplayName(),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function post(Post $post, User $viewer): array
    {
        $likes = [];
        $likedByMe = false;
        foreach ($post->getLikes() as $like) {
            $likes[] = $this->user($like->getUser());
            if ($like->getUser()->getId()->equals($viewer->getId())) {
                $likedByMe = true;
            }
        }

        $comments = [];
        foreach ($post->getComments() as $comment) {
            if ($comment->getParent() !== null) {
                continue;
            }
            $comments[] = $this->comment($comment, $viewer);
        }

        return [
            'id' => $post->getId()->toRfc4122(),
            'content' => $post->getContent(),
            'visibility' => $post->getVisibility(),
            'imageUrl' => $this->absoluteUrl($post->getImagePath()),
            'createdAt' => $post->getCreatedAt()->format(DATE_ATOM),
            'author' => $this->user($post->getAuthor()),
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'comments' => $comments,
        ];
    }

    private function absoluteUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $path;
        }

        return $request->getSchemeAndHttpHost() . $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function comment(Comment $comment, User $viewer): array
    {
        $likes = [];
        $likedByMe = false;

        foreach ($comment->getLikes() as $like) {
            $likes[] = $this->user($like->getUser());
            if ($like->getUser()->getId()->equals($viewer->getId())) {
                $likedByMe = true;
            }
        }

        $replies = [];
        foreach ($comment->getReplies() as $reply) {
            $replies[] = $this->comment($reply, $viewer);
        }

        return [
            'id' => $comment->getId()->toRfc4122(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'author' => $this->user($comment->getAuthor()),
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'replies' => $replies,
        ];
    }
}
