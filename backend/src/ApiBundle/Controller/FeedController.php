<?php

namespace ApiBundle\Controller;

use ApiBundle\Attribute\RateLimit;
use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\FeedValidator;
use CoreBundle\Entity\User;
use CoreBundle\Service\Feed as FeedService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class FeedController extends BaseController
{
    private readonly FeedService $feedService;

    private readonly FeedValidator $feedValidator;

    public function __construct(FeedService $feedService, FeedValidator $feedValidator)
    {
        parent::__construct();
        $this->feedService = $feedService;
        $this->feedValidator = $feedValidator;
    }

    #[Route('/feed', name: 'api_feed', methods: ['GET'])]
    #[RateLimit(60, 60)]
    public function feed(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $query = trim((string) $request->query->get('q', ''));
        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));

        try {
            $this->feedValidator->setAction('feed')->validate([
                'q' => $query,
                'limit' => $limit,
                'offset' => $offset,
            ]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        return $this->json($this->feedService->feed($user, $query, $limit, $offset));
    }

    #[Route('/posts', name: 'api_post_create', methods: ['POST'])]
    #[RateLimit(20, 60)]
    public function createPost(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $payload['visibility'] = $payload['visibility'] ?? 'public';

        try {
            $this->feedValidator->setAction('create_post')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->feedService->createPost(
                $user,
                (string) $payload['content'],
                (string) $payload['visibility'],
                $request->files->get('image') instanceof UploadedFile ? $request->files->get('image') : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result, 201);
    }

    #[Route('/posts/{id}/comments', name: 'api_post_comment_create', methods: ['POST'])]
    #[RateLimit(30, 60)]
    public function addComment(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $payload = $this->feedService->extractJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        $payload['id'] = $id;

        try {
            $this->feedValidator->setAction('add_comment')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->addComment($user, $id, (string) ($payload['content'] ?? ''));
        if ($result === null) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json($result, 201);
    }

    #[Route('/comments/{id}/replies', name: 'api_comment_reply_create', methods: ['POST'])]
    #[RateLimit(30, 60)]
    public function addReply(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $payload = $this->feedService->extractJsonPayload($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        $payload['id'] = $id;

        try {
            $this->feedValidator->setAction('add_reply')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->addReply($user, $id, (string) ($payload['content'] ?? ''));
        if ($result === null) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        return $this->json($result, 201);
    }

    #[Route('/posts/{id}/likes/toggle', name: 'api_post_like_toggle', methods: ['POST'])]
    #[RateLimit(60, 60)]
    public function togglePostLike(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->feedValidator->setAction('toggle_post_like')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->togglePostLike($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/posts/{id}', name: 'api_post_delete', methods: ['DELETE'])]
    #[RateLimit(20, 60)]
    public function deletePost(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->feedValidator->setAction('delete_post')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->deletePost($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Post not found or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/comments/{id}/likes/toggle', name: 'api_comment_like_toggle', methods: ['POST'])]
    #[RateLimit(60, 60)]
    public function toggleCommentLike(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->feedValidator->setAction('toggle_comment_like')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->toggleCommentLike($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/posts/{id}/likes', name: 'api_post_likes', methods: ['GET'])]
    #[RateLimit(60, 60)]
    public function postLikes(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->feedValidator->setAction('post_likes')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->postLikes($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/comments/{id}/likes', name: 'api_comment_likes', methods: ['GET'])]
    #[RateLimit(60, 60)]
    public function commentLikes(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->feedValidator->setAction('comment_likes')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->feedService->commentLikes($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/discover', name: 'api_discovery', methods: ['GET'])]
    #[RateLimit(30, 60)]
    public function discovery(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(3, min(24, (int) $request->query->get('limit', 12)));

        try {
            $this->feedValidator->setAction('discovery')->validate(['limit' => $limit]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        return $this->json($this->feedService->discovery($user, $limit));
    }

    #[Route('/discover/search', name: 'api_discovery_search', methods: ['GET'])]
    #[RateLimit(30, 60)]
    public function discoverySearch(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $query = (string) $request->query->get('q', '');
        $limit = max(5, min(40, (int) $request->query->get('limit', 20)));

        try {
            $this->feedValidator->setAction('discovery_search')->validate([
                'q' => $query,
                'limit' => $limit,
            ]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        return $this->json($this->feedService->discoverySearch($user, $query, $limit));
    }

    #[Route('/discover/topics', name: 'api_discovery_topics', methods: ['GET'])]
    #[RateLimit(30, 60)]
    public function discoveryTopics(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 12)));

        try {
            $this->feedValidator->setAction('discovery_topics')->validate(['limit' => $limit]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        return $this->json($this->feedService->trendingTopics($limit));
    }
}
