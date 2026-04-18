<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\ReactionValidator;
use CoreBundle\Entity\User;
use CoreBundle\Service\Reaction as ReactionService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ReactionController extends BaseController
{
    private readonly ReactionService $reactionService;

    private readonly ReactionValidator $reactionValidator;

    public function __construct(ReactionService $reactionService, ReactionValidator $reactionValidator)
    {
        parent::__construct();
        $this->reactionService = $reactionService;
        $this->reactionValidator = $reactionValidator;
    }

    #[Route('/reactions/catalog', name: 'api_reaction_catalog', methods: ['GET'])]
    public function catalog(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->reactionService->catalog());
    }

    #[Route('/reactions/toggle', name: 'api_reaction_toggle', methods: ['POST'])]
    public function toggle(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);

        try {
            $this->reactionValidator->setAction('toggle_reaction')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->reactionService->toggle(
                $user,
                (string) $payload['targetType'],
                (string) $payload['targetId'],
                (string) $payload['type'],
            );
        } catch (UniqueConstraintViolationException) {
            // Concurrent clicks can hit the unique key race; return the current canonical state.
            $result = $this->reactionService->targetReactions($user, (string) $payload['targetType'], (string) $payload['targetId']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result);
    }

    #[Route('/reactions', name: 'api_reaction_target', methods: ['GET'])]
    public function targetReactions(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = [
            'targetType' => (string) $request->query->get('targetType', ''),
            'targetId' => (string) $request->query->get('targetId', ''),
        ];

        try {
            $this->reactionValidator->setAction('target_reactions')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->reactionService->targetReactions($user, $payload['targetType'], $payload['targetId']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result);
    }

    #[Route('/reactions/summaries', name: 'api_reaction_summaries', methods: ['POST'])]
    public function batchTargetReactions(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->extractPayload($request);

        try {
            $this->reactionValidator->setAction('batch_target_reactions')->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->reactionService->batchTargetReactions($user, (array) ($payload['targets'] ?? []));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result);
    }

    /**
     * @return array<string,mixed>
     */
    private function extractPayload(Request $request): array
    {
        $payload = $this->combineRequestData($request);
        if ($payload !== []) {
            return $payload;
        }

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
