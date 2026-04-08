<?php

namespace ApiBundle\EventListener;

use ApiBundle\Attribute\RateLimit as RateLimitAttribute;
use CoreBundle\Exception\RateLimitException;
use CoreBundle\Service\Request\RateLimit as RateLimitService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

#[AsEventListener(event: ControllerEvent::class, method: 'onKernelController')]
class RateLimitListener
{
    private readonly RateLimitService $rateLimitService;
    private ControllerEvent $event;

    private readonly Security $security;

    public function __construct(RateLimitService $rateLimitService, Security $security)
    {
        $this->rateLimitService = $rateLimitService;
        $this->security = $security;
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
        [$controller, $method] = $controllers;

        try {
            $controller = new \ReflectionClass($controller);
        } catch (\ReflectionException) {
            throw new \RuntimeException('Failed to read method reflection!');
        }

        $this->handleMethodAttributes($controller, $method);
    }

    private function handleMethodAttributes(\ReflectionClass $controller, string $method): void
    {
        $method = $controller->getMethod($method);
        $attributes = $method->getAttributes(RateLimitAttribute::class);

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

        try {
            $this->rateLimitService
                ->limitRate($limit, $period);
        } catch (RateLimitException $e) {
            $this->event->setController(function () use ($e) {
                $user = $this->security->getUser();
                if ($user && 688 === $user->getId()) {
                    $errorMessage = \sprintf(
                        "Message: %s\nFile: %s\nLine: %d\nTrace:\n%s%s",
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString(),
                        \PHP_EOL
                    );
                    error_log($errorMessage, 3, '/var/www/log/rate_limit.log');
                }

                return new JsonResponse(['error' => 'Rate limit exceeded.'], Response::HTTP_TOO_MANY_REQUESTS);
            });
        }
    }
}
