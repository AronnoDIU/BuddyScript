<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiFormatter
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function user(User $user): array
    {
        return [
            'id' => $user->id->toRfc4122(),
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
        foreach ($post->likes as $like) {
            $likes[] = $this->user($like->getUser());
            if ($like->getUser()->id->equals($viewer->id)) {
                $likedByMe = true;
            }
        }

        $comments = [];
        foreach ($post->comments as $comment) {
            if ($comment->getParent() !== null) {
                continue;
            }
            $comments[] = $this->comment($comment, $viewer);
        }

        return [
            'id' => $post->id->toRfc4122(),
            'content' => $post->getContent(),
            'visibility' => $post->getVisibility(),
            'imageUrl' => $this->absoluteUrl($post->getImagePath()),
            'createdAt' => $post->createdAt->format(DATE_ATOM),
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

        foreach ($comment->likes as $like) {
            $likes[] = $this->user($like->getUser());
            if ($like->getUser()->id->equals($viewer->id)) {
                $likedByMe = true;
            }
        }

        $replies = [];
        foreach ($comment->replies as $reply) {
            $replies[] = $this->comment($reply, $viewer);
        }

        return [
            'id' => $comment->id->toRfc4122(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->createdAt->format(DATE_ATOM),
            'author' => $this->user($comment->getAuthor()),
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'replies' => $replies,
        ];
    }
}
