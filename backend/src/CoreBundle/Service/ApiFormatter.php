<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Comment;
use CoreBundle\Entity\Post;
use CoreBundle\Entity\User;
use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupMembership;
use CoreBundle\Entity\Community\GroupPost;
use CoreBundle\Entity\Community\GroupPostComment;
use CoreBundle\Entity\Community\Page;
use CoreBundle\Entity\Community\PageMembership;
use CoreBundle\Entity\Community\PagePost;
use CoreBundle\Entity\Community\PagePostComment;
use CoreBundle\Entity\Community\Event;
use CoreBundle\Entity\Community\EventMembership;
use CoreBundle\Entity\Community\EventPost;
use CoreBundle\Entity\Community\EventPostComment;
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
            'avatarUrl' => $this->avatarUrl($user),
        ];
    }

    private function avatarUrl(User $user): string
    {
        $email = $user->getEmail()
                |> trim(...)
                |> mb_strtolower(...);
        $hash = md5($email);

        return sprintf('https://www.gravatar.com/avatar/%s?d=identicon&s=128', $hash);
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
            'hashtags' => $post->getHashtags(),
            'topics' => $post->getTopics(),
            'createdAt' => $post->getCreatedAt()->format(DATE_ATOM),
            'author' => $this->user($post->getAuthor()),
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'comments' => $comments,
            'canDelete' => $post->getAuthor()->getId()->equals($viewer->getId()),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function discoveryCard(Post $post, User $viewer, string $kind): array
    {
        $base = $this->post($post, $viewer);

        return [
            'kind' => $kind,
            'post' => $base,
            'preview' => [
                'title' => $post->getAuthor()->getDisplayName(),
                'subtitle' => $post->getCreatedAt()->format('M j, H:i'),
                'mediaUrl' => $this->absoluteUrl($post->getImagePath()),
                'score' => $base['likesCount'] + count($base['comments']) * 2,
            ],
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

    /**
     * @param array<string, mixed> $stats
     *
     * @return array<string, mixed>
     */
    public function profile(User $profileUser, User $viewer, array $stats = []): array
    {
        $isMe = $profileUser->getId()->equals($viewer->getId());
        $safeStats = [
            'postsCount' => (int) ($stats['postsCount'] ?? 0),
            'publicPostsCount' => (int) ($stats['publicPostsCount'] ?? 0),
            'privatePostsCount' => (int) ($stats['privatePostsCount'] ?? 0),
            'likesReceivedCount' => (int) ($stats['likesReceivedCount'] ?? 0),
            'commentsReceivedCount' => (int) ($stats['commentsReceivedCount'] ?? 0),
        ];

        return [
            'id' => $profileUser->getId()->toRfc4122(),
            'firstName' => $profileUser->getFirstName(),
            'lastName' => $profileUser->getLastName(),
            'displayName' => $profileUser->getDisplayName(),
            'email' => $isMe ? $profileUser->getEmail() : null,
            'isMe' => $isMe,
            'joinedAt' => $profileUser->getCreatedAt()->format(DATE_ATOM),
            'avatarUrl' => $this->avatarUrl($profileUser),
            'coverUrl' => null,
            'bio' => null,
            'viewerPermissions' => [
                'canViewPrivatePosts' => $isMe,
                'canViewEmail' => $isMe,
            ],
            'stats' => $safeStats,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function group(Group $group, User $viewer): array
    {
        return [
            'id' => $group->getId()->toRfc4122(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'avatarUrl' => $this->absoluteUrl($group->getAvatarPath()),
            'visibility' => $group->getVisibility(),
            'settings' => $group->getSettings(),
            'createdAt' => $group->getCreatedAt()->format(DATE_ATOM),
            'creator' => $this->user($group->getCreator()),
            'memberCount' => $group->getMemberCount(),
            'postCount' => $group->getPostCount(),
            'userRole' => $group->getMembershipRole($viewer),
            'permissions' => [
                'view' => $group->hasPermission($viewer, 'view'),
                'post' => $group->hasPermission($viewer, 'post'),
                'moderate' => $group->hasPermission($viewer, 'moderate'),
                'admin' => $group->hasPermission($viewer, 'admin'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function groupMembership(GroupMembership $membership, User $viewer): array
    {
        return [
            'id' => $membership->getId()->toRfc4122(),
            'role' => $membership->getRole(),
            'joinedAt' => $membership->getJoinedAt()->format(DATE_ATOM),
            'user' => $this->user($membership->getUser()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function page(Page $page, User $viewer): array
    {
        $isOwner = $page->getCreator()->getId()->equals($viewer->getId());

        return [
            'id' => $page->getId()->toRfc4122(),
            'name' => $page->getName(),
            'description' => $page->getDescription(),
            'avatarUrl' => $this->absoluteUrl($page->getAvatarPath()),
            'coverUrl' => $this->absoluteUrl($page->getCoverPath()),
            'category' => $page->getCategory(),
            'settings' => $page->getSettings(),
            'createdAt' => $page->getCreatedAt()->format(DATE_ATOM),
            'creator' => $this->user($page->getCreator()),
            'memberCount' => $page->getMemberCount(),
            'followersCount' => $page->getMemberCount(),
            'postsCount' => $page->getPostCount(),
            'postCount' => $page->getPostCount(),
            'isOwner' => $isOwner,
            'isFollowing' => $isOwner || $page->isMember($viewer),
            'userRole' => $page->getMembershipRole($viewer),
            'permissions' => [
                'view' => $page->hasPermission($viewer, 'view'),
                'post' => $page->hasPermission($viewer, 'post'),
                'moderate' => $page->hasPermission($viewer, 'edit'),
                'admin' => $page->hasPermission($viewer, 'admin'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pageMembership(PageMembership $membership, User $viewer): array
    {
        return [
            'id' => $membership->getId()->toRfc4122(),
            'role' => $membership->getRole(),
            'joinedAt' => $membership->getJoinedAt()->format(DATE_ATOM),
            'user' => $this->user($membership->getUser()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pagePost(PagePost $post, User $viewer): array
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
            $comments[] = $this->pagePostComment($comment, $viewer);
        }

        return [
            'id' => $post->getId()->toRfc4122(),
            'content' => $post->getContent(),
            'imageUrl' => $this->absoluteUrl($post->getImagePath()),
            'hashtags' => $post->getHashtags(),
            'createdAt' => $post->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $post->getUpdatedAt()?->format(DATE_ATOM),
            'author' => $this->user($post->getAuthor()),
            'page' => [
                'id' => $post->getPage()->getId()->toRfc4122(),
                'name' => $post->getPage()->getName(),
            ],
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'commentsCount' => count($comments),
            'comments' => $comments,
            'canDelete' => $post->getAuthor()->getId()->equals($viewer->getId()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pagePostComment(PagePostComment $comment, User $viewer): array
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
            $replies[] = $this->pagePostComment($reply, $viewer);
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

    /**
     * @return array<string, mixed>
     */
    public function event(Event $event, User $viewer): array
    {
        $isOwner = $event->getCreator()->getId()->equals($viewer->getId());

        return [
            'id' => $event->getId()->toRfc4122(),
            'name' => $event->getName(),
            'description' => $event->getDescription(),
            'avatarUrl' => $this->absoluteUrl($event->getAvatarPath()),
            'type' => $event->getType(),
            'status' => $event->getStatus(),
            'startDate' => $event->getStartDate()->format(DATE_ATOM),
            'endDate' => $event->getEndDate()->format(DATE_ATOM),
            'location' => $event->getLocation(),
            'onlineUrl' => $event->getOnlineUrl(),
            'maxAttendees' => $event->getMaxAttendees(),
            'settings' => $event->getSettings(),
            'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
            'creator' => $this->user($event->getCreator()),
            'memberCount' => $event->getAttendeeCount(),
            'attendeeCount' => $event->getAttendeeCount(),
            'postCount' => $event->getPostCount(),
            'isOwner' => $isOwner,
            'isMember' => $isOwner || $event->isMember($viewer),
            'userRole' => $event->getMembershipRole($viewer),
            'permissions' => [
                'view' => $event->hasPermission($viewer, 'view'),
                'post' => $event->hasPermission($viewer, 'post'),
                'moderate' => $event->hasPermission($viewer, 'moderate'),
                'admin' => $event->hasPermission($viewer, 'admin'),
                'attend' => $event->hasPermission($viewer, 'attend'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eventMembership(EventMembership $membership, User $viewer): array
    {
        return [
            'id' => $membership->getId()->toRfc4122(),
            'role' => $membership->getRole(),
            'joinedAt' => $membership->getJoinedAt()->format(DATE_ATOM),
            'user' => $this->user($membership->getUser()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eventPost(EventPost $post, User $viewer): array
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
            $comments[] = $this->eventPostComment($comment, $viewer);
        }

        return [
            'id' => $post->getId()->toRfc4122(),
            'content' => $post->getContent(),
            'imageUrl' => $this->absoluteUrl($post->getImagePath()),
            'hashtags' => $post->getHashtags(),
            'createdAt' => $post->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $post->getUpdatedAt()?->format(DATE_ATOM),
            'author' => $this->user($post->getAuthor()),
            'event' => [
                'id' => $post->getEvent()->getId()->toRfc4122(),
                'name' => $post->getEvent()->getName(),
            ],
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'commentsCount' => count($comments),
            'comments' => $comments,
            'canDelete' => $post->getAuthor()->getId()->equals($viewer->getId()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eventPostComment(EventPostComment $comment, User $viewer): array
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
            $replies[] = $this->eventPostComment($reply, $viewer);
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

    /**
     * @return array<string, mixed>
     */
    public function groupPost(GroupPost $post, User $viewer): array
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
            $comments[] = $this->groupPostComment($comment, $viewer);
        }

        return [
            'id' => $post->getId()->toRfc4122(),
            'content' => $post->getContent(),
            'imageUrl' => $this->absoluteUrl($post->getImagePath()),
            'hashtags' => $post->getHashtags(),
            'createdAt' => $post->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $post->getUpdatedAt()?->format(DATE_ATOM),
            'author' => $this->user($post->getAuthor()),
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
     * @return array<string, mixed>
     */
    public function groupPostComment(GroupPostComment $comment, User $viewer): array
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
            $replies[] = $this->groupPostComment($reply, $viewer);
        }

        return [
            'id' => $comment->getId()->toRfc4122(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $comment->getUpdatedAt()?->format(DATE_ATOM),
            'author' => $this->user($comment->getAuthor()),
            'likesCount' => count($likes),
            'likes' => $likes,
            'likedByMe' => $likedByMe,
            'replies' => $replies,
            'repliesCount' => $comment->getReplyCount(),
            'isReply' => $comment->isReply(),
        ];
    }
}
