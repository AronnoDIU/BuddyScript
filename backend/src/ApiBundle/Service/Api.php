<?php

namespace ApiBundle\Service;

use CoreBundle\Service\Cache as CacheService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class Api
 */
class Api
{
    private readonly AuthorizationCheckerInterface $authorizationChecker;

    private readonly SerializerInterface $serializerService;

    private readonly CacheService $cacheService;

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

    public function preventDoubleClick(int $objectId, string $object, string $action)
    {
        $key = \sprintf('double-click-%s-%s-%s', $action, $object, $objectId);
        $cacheItem = $this->cacheService->getItem($key);
        if ($cacheItem->isHit()) {
            throw new \Exception('Hey! please slow down! You are trying to access too fast.');
        }

        $expiresAfter = 10;
        if ('deposit' === $object && 'approve' === $action) {
            $expiresAfter = 300;
        }

        $cacheItem
            ->set(true)
            ->expiresAfter($expiresAfter);
        $this->cacheService->save($cacheItem);
    }
}
