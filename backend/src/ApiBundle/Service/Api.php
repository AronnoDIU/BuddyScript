<?php

namespace ApiBundle\Service;

use CoreBundle\Service\Cache as CacheService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class Api
 */
readonly class Api
{
    private AuthorizationCheckerInterface $authorizationChecker;

    private SerializerInterface $serializerService;

    private CacheService $cacheService;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SerializerInterface $serializerService,
        CacheService $cacheService,
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->serializerService = $serializerService;
        $this->cacheService = $cacheService;
    }

    public function getSerializerService(): SerializerInterface
    {
        return $this->serializerService;
    }

    public function getCacheService(): CacheService
    {
        return $this->cacheService;
    }

    public function isGranted(string $role): bool
    {
        return $this->authorizationChecker->isGranted($role);
    }
}
