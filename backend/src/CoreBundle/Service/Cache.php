<?php

namespace CoreBundle\Service;

use JMS\Serializer\SerializerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class Cache
{
    protected TagAwareCacheInterface $cache;

    protected SerializerInterface $serializer;

    public function __construct(TagAwareCacheInterface $cache, SerializerInterface $serializer)
    {
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    public function getItem(string $key): ?CacheItem
    {
        return $this->cache->getItem($key);
    }

    public function save(CacheItem $item): void
    {
        $this->cache->save($item);
    }

    public function isCacheDisabledOrMiss(CacheItem $cacheData): bool
    {
        if (!$cacheData->isHit()) {
            return true;
        }

        return false;
    }

    public function serialize($data, array $groups): string
    {
        return $this->serializer->serialize($data, $groups);
    }

    public function clearByTag(string $tag): void
    {
        try {
            $this->cache->invalidateTags([$tag]);
        } catch (InvalidArgumentException $e) {
            new \Exception($e->getMessage());
        }
    }
}
