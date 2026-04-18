<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\SocialGraphValidator;
use CoreBundle\Entity\User;
use CoreBundle\Service\SocialGraph as SocialGraphService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SocialGraphController extends BaseController
{
    private readonly SocialGraphService $socialGraphService;

    private readonly SocialGraphValidator $socialGraphValidator;

    public function __construct(SocialGraphService $socialGraphService, SocialGraphValidator $socialGraphValidator)
    {
        parent::__construct();
        $this->socialGraphService = $socialGraphService;
        $this->socialGraphValidator = $socialGraphValidator;
    }

    #[Route('/social/overview', name: 'api_social_overview', methods: ['GET'])]
    public function overview(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->socialGraphService->overview($user));
    }

    #[Route('/social/requests', name: 'api_social_request_send', methods: ['POST'])]
    public function sendRequest(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        if ($payload === []) {
            $payload = $this->socialGraphServicePayload($request);
        }

        try {
            $this->socialGraphValidator->setAction('send_request')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->socialGraphService->sendRequest(
                $user,
                (string) $payload['targetUserId'],
                isset($payload['listKey']) ? (string) $payload['listKey'] : null,
            );
        } catch (\DomainException $e) {
            return $this->json(['message' => $e->getMessage()], 409);
        }

        return $this->json($result, 201);
    }

    #[Route('/social/requests/{id}/respond', name: 'api_social_request_respond', methods: ['POST'])]
    public function respondRequest(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->socialGraphServicePayload($request);
        $payload['id'] = $id;

        try {
            $this->socialGraphValidator->setAction('respond_request')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->socialGraphService->respondToRequest($user, $id, (string) $payload['status']);
        } catch (\DomainException $e) {
            return $this->json(['message' => $e->getMessage()], 409);
        }

        if ($result === null) {
            return $this->json(['message' => 'Connection not found.'], 404);
        }

        return $this->json($result);
    }

    /**
     * @return array<string,mixed>
     */
    private function socialGraphServicePayload(Request $request): array
    {
        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
