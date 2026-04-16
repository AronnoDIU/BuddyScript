<?php

declare(strict_types=1);

namespace ApiBundle\Controller\Safety;

use ApiBundle\Controller\BaseController;
use CoreBundle\Entity\User;
use CoreBundle\Service\Safety\TrustSafetyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

class TrustSafetyController extends BaseController
{
    public function __construct(
        private readonly TrustSafetyService $trustSafetyService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[Route('/safety/reports', name: 'api_safety_reports_create', methods: ['POST'])]
    public function createReport(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);
        $required = ['targetType', 'targetId', 'category', 'reason'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                return $this->json(['message' => sprintf('%s is required.', $field)], 422);
            }
        }

        try {
            $result = $this->trustSafetyService->submitReport($user, $payload);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result, 201);
    }

    #[Route('/safety/reports/me', name: 'api_safety_reports_me', methods: ['GET'])]
    public function myReports(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->trustSafetyService->myReports($user));
    }

    #[Route('/safety/blocks/{userId}', name: 'api_safety_block_user', methods: ['POST'])]
    public function blockUser(string $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $target = $this->findUser($userId);
        if (!$target instanceof User) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        try {
            $result = $this->trustSafetyService->blockUser($user, $target);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result, 201);
    }

    #[Route('/safety/blocks/{userId}', name: 'api_safety_unblock_user', methods: ['DELETE'])]
    public function unblockUser(string $userId, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $target = $this->findUser($userId);
        if (!$target instanceof User) {
            return $this->json(['message' => 'User not found.'], 404);
        }

        return $this->json($this->trustSafetyService->unblockUser($user, $target));
    }

    #[Route('/safety/blocks', name: 'api_safety_list_blocks', methods: ['GET'])]
    public function listBlockedUsers(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->trustSafetyService->blockedUsers($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(Request $request): array
    {
        $payload = $request->request->all();
        if ($payload !== []) {
            return $payload;
        }

        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function findUser(string $userId): ?User
    {
        try {
            return $this->entityManager->find(User::class, Uuid::fromString($userId));
        } catch (\Throwable $e) {
            return null;
        }
    }
}

