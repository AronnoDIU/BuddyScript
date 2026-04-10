<?php

namespace ApiBundle\Controller\Community;

use ApiBundle\Controller\BaseController;
use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\Community\GroupValidator;
use CoreBundle\Entity\Community\Group;
use CoreBundle\Entity\Community\GroupMembership;
use CoreBundle\Entity\User;
use CoreBundle\Service\ApiFormatter;
use CoreBundle\Service\Community\GroupService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class GroupController extends BaseController
{
    private readonly GroupService $groupService;
    private readonly GroupValidator $groupValidator;
    private readonly ApiFormatter $formatter;

    public function __construct(GroupService $groupService, GroupValidator $groupValidator, ApiFormatter $formatter)
    {
        parent::__construct();
        $this->groupService = $groupService;
        $this->groupValidator = $groupValidator;
        $this->formatter = $formatter;
    }

    #[Route('/groups', name: 'api_groups_create', methods: ['POST'])]
    public function createGroup(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $payload = $this->normalizeSettingsPayload($payload);

        try {
            $this->groupValidator->setAction('create_group')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupService->createGroup(
                $user,
                (string) $payload['name'],
                (string) ($payload['description'] ?? ''),
                (string) ($payload['visibility'] ?? Group::VISIBILITY_PUBLIC),
                $request->files->get('avatar') instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? $request->files->get('avatar') : null,
                (array) ($payload['settings'] ?? [])
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result, 201);
    }

    #[Route('/groups', name: 'api_groups_list', methods: ['GET'])]
    public function listGroups(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $query = (string) $request->query->get('q', '');

        try {
            $this->groupValidator->setAction('list_groups')->validate(['limit' => $limit, 'q' => $query]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        if ($query !== '') {
            $groups = $this->groupService->searchGroups($user, $query, $limit);
        } else {
            $groups = $this->groupService->getGroupsForUser($user, $limit);
        }

        return $this->json([
            'groups' => array_map(fn (Group $group): array => $this->formatter->group($group, $user), $groups),
            'query' => $query,
        ]);
    }

    #[Route('/groups/public', name: 'api_groups_public', methods: ['GET'])]
    public function listPublicGroups(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));

        try {
            $this->groupValidator->setAction('list_public_groups')->validate(['limit' => $limit]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $groups = $this->groupService->getPublicGroups($limit);

        return $this->json([
            'groups' => array_map(fn (Group $group): array => $this->formatter->group($group, $user), $groups),
        ]);
    }

    #[Route('/groups/{id}', name: 'api_group_get', methods: ['GET'])]
    public function getGroup(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupValidator->setAction('get_group')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $group = $this->groupService->getAccessibleGroup($id, $user);
        if ($group === null) {
            return $this->json(['message' => 'Group not found.'], 404);
        }

        return $this->json(['group' => $this->formatter->group($group, $user)]);
    }

    #[Route('/groups/{id}', name: 'api_group_update', methods: ['PUT'])]
    public function updateGroup(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $payload = $this->normalizeSettingsPayload($payload);
        $payload['id'] = $id;

        try {
            $this->groupValidator->setAction('update_group')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupService->updateGroup(
                $user,
                $id,
                (string) ($payload['name'] ?? ''),
                (string) ($payload['description'] ?? ''),
                (string) ($payload['visibility'] ?? ''),
                $request->files->get('avatar') instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? $request->files->get('avatar') : null,
                (array) ($payload['settings'] ?? [])
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Group not found or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/groups/{id}', name: 'api_group_delete', methods: ['DELETE'])]
    public function deleteGroup(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupValidator->setAction('delete_group')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $deleteGroup = [$this->groupService, 'deleteGroup'];
        $result = is_callable($deleteGroup) ? call_user_func($deleteGroup, $user, $id) : null;
        if ($result === null) {
            return $this->json(['message' => 'Group not found or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/groups/{id}/join', name: 'api_group_join', methods: ['POST'])]
    public function joinGroup(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupValidator->setAction('join_group')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupService->joinGroup($user, $id);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Group not found.'], 404);
        }

        return $this->json($result, 201);
    }

    #[Route('/groups/{id}/leave', name: 'api_group_leave', methods: ['POST'])]
    public function leaveGroup(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupValidator->setAction('leave_group')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupService->leaveGroup($user, $id);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Group not found or not a member.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/groups/{id}/members', name: 'api_group_members', methods: ['GET'])]
    public function getGroupMembers(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(5, min(50, (int) $request->query->get('limit', 20)));
        $role = (string) $request->query->get('role', '');

        try {
            $this->groupValidator->setAction('get_members')->validate(['id' => $id, 'limit' => $limit, 'role' => $role]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $group = $this->groupService->getAccessibleGroup($id, $user);
        if ($group === null) {
            return $this->json(['message' => 'Group not found.'], 404);
        }

        $members = $this->groupService->getGroupMembers($group, $role ?: null, $limit);

        return $this->json([
            'members' => array_map(fn (GroupMembership $membership): array => $this->formatter->groupMembership($membership, $user), $members),
        ]);
    }

    #[Route('/groups/{id}/members/{userId}', name: 'api_group_update_member', methods: ['PUT'])]
    public function updateGroupMember(string $id, string $userId, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $payload['id'] = $id;
        $payload['userId'] = $userId;

        try {
            $this->groupValidator->setAction('update_member')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupService->updateMemberRole($user, $id, $userId, (string) ($payload['role'] ?? ''));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Group not found, member not found, or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/groups/{id}/members/{userId}', name: 'api_group_remove_member', methods: ['DELETE'])]
    public function removeGroupMember(string $id, string $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->groupValidator->setAction('remove_member')->validate(['id' => $id, 'userId' => $userId]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->groupService->removeMember($user, $id, $userId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Group not found, member not found, or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    private function normalizeSettingsPayload(array $payload): array
    {
        if (!isset($payload['settings']) || is_array($payload['settings'])) {
            return $payload;
        }

        if (!is_string($payload['settings'])) {
            return $payload;
        }

        $decoded = json_decode($payload['settings'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $payload['settings'] = $decoded;
        }

        return $payload;
    }
}
