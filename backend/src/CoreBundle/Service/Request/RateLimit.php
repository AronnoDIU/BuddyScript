<?php

namespace CoreBundle\Service\Request;

use CoreBundle\Entity\Request\RateLimit\Info as InfoEntity;
use CoreBundle\Exception\RateLimitException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

readonly class RateLimit
{
    private Request $request;

    private CacheInterface $cache;

    private ParameterBagInterface $parameterBag;

    public function __construct(RequestStack $request, CacheInterface $cache, ParameterBagInterface $parameterBag)
    {
        $this->request = $request->getCurrentRequest();
        $this->cache = $cache;
        $this->parameterBag = $parameterBag;
    }

    public function limitRate($limit, $period): InfoEntity|bool
    {
        $key = $this->getKey();
        if ('test' === $this->parameterBag->get('kernel.environment')
            || null === $key
            || ('stage' === $this->parameterBag->get('kernel.environment') && $this->request->headers->has('X-TEST'))) {
            return false;
        }

        $item = $this->cache->getItem($key);
        if (!$item->isHit()) {
            $info = $this->createRate($key, $limit, $period);
        } else {
            $info = $item->get();
            $reset = \is_array($info) ? $info['reset'] : $info->getResetTimestamp();
            // Reset the rate limits
            if (time() >= $reset) {
                $this->resetRate($key);
                $info = $this->createRate($key, $limit, $period);
            }
        }

        if (\is_array($info)) {
            $info = InfoEntity::fromArray($info);
        }

        $info->addCall();
        if ($info->getCalls() > $info->getLimit()) {
            $output = [];
            $excludeRoutes = [];
            $route = $this->request->attributes->get('_route');

            // excluding routes
            $excludeRoutes[] = '_wdt';

            $excludePattern = \sprintf('/(%s)/', implode('|', $excludeRoutes));
            preg_match($excludePattern, (string) $route, $output);

            if (!empty($output)) {
                return true;
            }

            throw new RateLimitException('Rate limit exceeded');
        }

        $item->set($info);
        $item->expiresAfter($info->getResetTimestamp() - time());
        $this->cache->save($item);

        return $this->createRateInfo($info->toArray());
    }

    private function createRate($key, $limit, $period): InfoEntity
    {
        $info = [
            'limit' => $limit,
            'calls' => 0,
            'reset' => time() + $period,
        ];
        $item = $this->cache->getItem($key);
        $item->set($info);
        $item->expiresAfter($period);

        $this->cache->save($item);

        return $this->createRateInfo($info);
    }

    private function resetRate($key): void
    {
        $this->cache->deleteItem($key);

    }

    private function createRateInfo(array $params): InfoEntity
    {
        $info = new InfoEntity();
        $info->setLimit($params['limit']);
        $info->setCalls($params['calls']);
        $info->setResetTimestamp($params['reset']);

        return $info;
    }

    private function getKey(): ?string
    {
        $request = $this->request;

        $clientIp = $this->getClientIp();

        if (null === $clientIp) {
            return null;
        }

        $route = $request->attributes->get('_route');

        $output = [];
        // excluding admin routes
        preg_match('/(admin|fos_|import_|dashboard_|system_|search_list)/', (string) $route, $output);

        if (!empty($output)) {
            return null;
        }

        return md5(\sprintf('rate.limit.%s.%s', $clientIp, $route));
    }

    private function getClientIp(): ?string
    {
        $clientIp = $this->request->getClientIp();
        $forwardedFor = $this->request->headers->get('x-forwarded-for');
        if (!empty($forwardedFor)) {
            $clientIp = $forwardedFor;
        }
        $cloudFlareProxy = $this->request->headers->get('cf-connecting-ip');
        if (!empty($cloudFlareProxy)) {
            $clientIp = $cloudFlareProxy;
        }

        return $clientIp;
    }
}
