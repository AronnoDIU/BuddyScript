<?php

declare(strict_types=1);

namespace ApiBundle\Controller\Safety;

use ApiBundle\Controller\BaseController;
use CoreBundle\Entity\User;
use CoreBundle\Service\Safety\PrivacyCheckupService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class PrivacyCheckupController extends BaseController
{
    public function __construct(
        private readonly PrivacyCheckupService $privacyCheckupService,
    ) {
        parent::__construct();
    }

    #[Route('/privacy-checkup', name: 'api_privacy_checkup_get', methods: ['GET'])]
    public function checkup(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->privacyCheckupService->checkup($user));
    }

    #[Route('/privacy-checkup', name: 'api_privacy_checkup_update', methods: ['PUT'])]
    public function update(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $request->request->all();
        if ($payload === []) {
            $content = trim($request->getContent());
            if ($content !== '') {
                try {
                    $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                    $payload = is_array($decoded) ? $decoded : [];
                } catch (\Exception) {
                    return $this->json(['message' => 'Invalid JSON payload.'], 422);
                }
            }
        }

        try {
            $result = $this->privacyCheckupService->update($user, $payload);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result);
    }
}

