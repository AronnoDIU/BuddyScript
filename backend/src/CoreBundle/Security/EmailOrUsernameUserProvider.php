<?php

declare(strict_types=1);

namespace CoreBundle\Security;

use CoreBundle\Entity\User;
use CoreBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class EmailOrUsernameUserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneByEmailOrUsername($identifier);
        if ($user instanceof User) {
            return $user;
        }

        $exception = new UserNotFoundException(sprintf('User "%s" was not found.', $identifier));
        $exception->setUserIdentifier($identifier);

        throw $exception;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $reloadedUser = $this->userRepository->findOneById($user->getId()->toRfc4122());
        if ($reloadedUser instanceof User) {
            return $reloadedUser;
        }

        $exception = new UserNotFoundException('User could not be reloaded.');
        $exception->setUserIdentifier($user->getUserIdentifier());

        throw $exception;
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, User::class, true);
    }
}

