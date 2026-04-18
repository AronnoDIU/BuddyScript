<?php

namespace CoreBundle\Service;

use CoreBundle\Entity\Post;
use CoreBundle\Entity\User;
use CoreBundle\Repository\PostRepository;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class Profile
{
    private EntityManagerInterface $entityManager;

    private ApiFormatter $formatter;

    public function __construct(
        EntityManagerInterface $entityManager,
        ApiFormatter $formatter,
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function show(string $profileId, User $viewer): ?array
    {
        $profileUser = $this->getUserRepository()->findOneById($profileId);
        if (!$profileUser instanceof User) {
            return null;
        }

        $posts = $this->getPostRepository()->findProfileFeedForUser($profileUser, $viewer);
        $stats = $this->getPostRepository()->buildProfileStatsForViewer($profileUser, $viewer);

        return [
            'profile' => $this->formatter->profile($profileUser, $viewer, $stats),
            'posts' => array_map(fn (Post $post): array => $this->formatter->post($post, $viewer), $posts),
        ];
    }

    private function getUserRepository(): UserRepository
    {
        $repository = $this->entityManager->getRepository(User::class);
        if (!$repository instanceof UserRepository) {
            throw new \LogicException('User repository is not configured correctly.');
        }

        return $repository;
    }

    private function getPostRepository(): PostRepository
    {
        $repository = $this->entityManager->getRepository(Post::class);
        if (!$repository instanceof PostRepository) {
            throw new \LogicException('Post repository is not configured correctly.');
        }

        return $repository;
    }
}
