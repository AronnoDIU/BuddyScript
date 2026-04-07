<?php

declare(strict_types=1);

namespace ApiBundle\Controller;

use CoreBundle\Entity\Post;
use CoreBundle\Entity\User;
use CoreBundle\Repository\PostRepository;
use CoreBundle\Repository\UserRepository;
use CoreBundle\Service\ApiFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PostRepository $postRepository,
        private readonly ApiFormatter $formatter,
    ) {
    }

    #[Route('/profiles/{id}', name: 'api_profile_show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?User $viewer): JsonResponse
    {
        if ($viewer === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $profileUser = $this->userRepository->findOneById($id);
        if (!$profileUser instanceof User) {
            return $this->json(['message' => 'Profile not found.'], 404);
        }

        $posts = $this->postRepository->findProfileFeedForUser($profileUser, $viewer);

        $stats = $this->postRepository->buildProfileStatsForViewer($profileUser, $viewer);

        return $this->json([
            'profile' => $this->formatter->profile($profileUser, $viewer, $stats),
            'posts' => array_map(fn (Post $post): array => $this->formatter->post($post, $viewer), $posts),
        ]);
    }
}

