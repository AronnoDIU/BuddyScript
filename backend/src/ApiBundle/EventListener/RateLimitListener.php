<?php

namespace ApiBundle\EventListener;

use ApiBundle\Attribute\RateLimit as RateLimitAttribute;
use CoreBundle\Exception\RateLimitException;
use CoreBundle\Service\Request\RateLimit as RateLimitService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

#[AsEventListener(event: ControllerEvent::class, method: 'onKernelController')]
class RateLimitListener
{
    private readonly RateLimitService $rateLimitService;
    private ControllerEvent $event;

    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controllers = $event->getController();
        if (!\is_array($controllers)) {
            return;
        }

        $this->event = $event;
        $this->handleAttributes($controllers);
    }

    private function handleAttributes(iterable $controllers): void
    {
        if (!\is_array($controllers) || !isset($controllers[0], $controllers[1])) {
            return;
        }

        [$controller, $methodName] = $controllers;

        try {
            $controller = new \ReflectionClass($controller);
        } catch (\ReflectionException) {
            throw new \RuntimeException('Failed to read method reflection!');
        }

        $this->handleMethodAttributes($controller, (string) $methodName);
    }

    private function handleMethodAttributes(\ReflectionClass $controller, string $method): void
    {
        $reflectionMethod = $controller->getMethod($method);
        $attributes = $reflectionMethod->getAttributes(RateLimitAttribute::class);

        if (\count($attributes) > 0) {
            /** @var RateLimitAttribute $attribute */
            $attribute = $attributes[0]->newInstance();
            $limit = $attribute->getLimit();
            $period = $attribute->getPeriod();
        } else {
            // Default for all endpoints without RateLimit attribute
            $limit = 10;
            $period = 2;
        }

        if ($limit <= 0 || $period <= 0) {
            $limit = 10;
            $period = 2;
        }

        try {
            $this->rateLimitService
                ->limitRate($limit, $period);
        } catch (RateLimitException $e) {
            $this->event->setController(function () use ($e) {
                return new JsonResponse([
                    'message' => 'Rate limit exceeded. Please try again later.',
                ], Response::HTTP_TOO_MANY_REQUESTS);
            });
        }
    }
}
