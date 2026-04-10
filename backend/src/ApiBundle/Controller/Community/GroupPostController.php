<?php

namespace ApiBundle\Controller\Community;

use ApiBundle\Controller\BaseController;
use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\Community\GroupPostValidator;
use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupPost;
use CoreBundle\Entity\User;
use CoreBundle\Service\ApiFormatter;
use CoreBundle\Service\Community\GroupPostService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class GroupPostController extends BaseController
{
    private readonly GroupPostService $groupPostService;
    private readonly GroupPostValidator $groupPostValidator;
    private readonly ApiFormatter $formatter;

    public function __construct(GroupPostService $groupPostService, GroupPostValidator $groupPostValidator, ApiFormatter $formatter)
    {
        parent::__construct();
        $this->groupPostService = $groupPostService;
        $this->groupPostValidator = $groupPostValidator;
        $this->formatter = $formatter;
    }

    #[Route('/groups/{id}/posts', name: 'api_group_posts_create', methods: ['POST'])]
    public function createPost(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $payload['groupId'] = $id;

        try {
            $this->groupPostValidator->setAction('create_post')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupPostService->createPost(
                $user,
                $id,
                (string) $payload['content'],
                $request->files->get('image') instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? $request->files->get('image') : null
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Group not found or insufficient permissions.'], 404);
        }

        return $this->json($result, 201);
    }

    #[Route('/groups/{id}/posts', name: 'api_group_posts_list', methods: ['GET'])]
    public function listPosts(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = (string) $request->query->get('q', '');

        try {
            $this->groupPostValidator->setAction('list_posts')->validate(['groupId' => $id, 'limit' => $limit, 'query' => $query]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            if ($query !== '') {
                $posts = $this->groupPostService->searchPosts($user, $id, $query, $limit);
            } else {
                $posts = $this->groupPostService->getPosts($user, $id, $limit);
            }
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json([
            'posts' => array_map(fn (GroupPost $post): array => $this->formatter->groupPost($post, $user), $posts),
            'query' => $query,
        ]);
    }

    #[Route('/group-posts/{id}', name: 'api_group_post_get', methods: ['GET'])]
    public function getPost(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupPostValidator->setAction('get_post')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $post = $this->groupPostService->getAccessiblePost($id, $user);
        if ($post === null) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json(['post' => $this->formatter->groupPost($post, $user)]);
    }

    #[Route('/group-posts/{id}/likes/toggle', name: 'api_group_post_like_toggle', methods: ['POST'])]
    public function toggleLike(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupPostValidator->setAction('toggle_like')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupPostService->toggleLike($user, $id);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/group-posts/{id}/comments', name: 'api_group_post_comment_create', methods: ['POST'])]
    public function addComment(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $payload['postId'] = $id;

        try {
            $this->groupPostValidator->setAction('add_comment')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupPostService->addComment($user, $id, (string) ($payload['content'] ?? ''));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Post not found.'], 404);
        }

        return $this->json($result, 201);
    }

    #[Route('/group-post-comments/{id}/likes/toggle', name: 'api_group_post_comment_like_toggle', methods: ['POST'])]
    public function toggleCommentLike(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupPostValidator->setAction('toggle_comment_like')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupPostService->toggleCommentLike($user, $id);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Comment not found.'], 404);
        }

        return $this->json($result);
    }
}
