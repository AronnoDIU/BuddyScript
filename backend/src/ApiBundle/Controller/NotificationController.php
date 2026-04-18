<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\NotificationValidator;
use CoreBundle\Entity\User;
use CoreBundle\Service\Notification as NotificationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class NotificationController extends BaseController
{
    private readonly NotificationService $notificationService;

    private readonly NotificationValidator $notificationValidator;

    public function __construct(NotificationService $notificationService, NotificationValidator $notificationValidator)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->notificationValidator = $notificationValidator;
    }

    #[Route('/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $limit = max(1, min(100, (int) $request->query->get('limit', 30)));

        return $this->json($this->notificationService->list($user, $limit));
    }

    #[Route('/notifications/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markRead(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->notificationValidator->setAction('mark_read')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->notificationService->markRead($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Notification not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/notifications/read-all', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->notificationService->markAllRead($user));
    }
}
