<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\ProfileValidator;
use CoreBundle\Entity\User;
use CoreBundle\Service\Profile as ProfileService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ProfileController extends BaseController
{
    private readonly ProfileService $profileService;

    private readonly ProfileValidator $profileValidator;

    public function __construct(ProfileService $profileService, ProfileValidator $profileValidator)
    {
        parent::__construct();
        $this->profileService = $profileService;
        $this->profileValidator = $profileValidator;
    }

    #[Route('/profiles/{id}', name: 'api_profile_show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?User $viewer): JsonResponse
    {
        if ($viewer === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $this->profileValidator->setAction('show')->validate(['id' => $id]);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        $result = $this->profileService->show($id, $viewer);
        if ($result === null) {
            return $this->json(['message' => 'Profile not found.'], 404);
        }

        return $this->json($result);
    }
}
