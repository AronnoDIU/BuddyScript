<?php

namespace ApiBundle\EventListener;

use CoreBundle\Service\Analytics;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PageViewSubscriber implements EventSubscriberInterface
{
    private Analytics $analyticsService;

    public function __construct(Analytics $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0], // High priority to ensure it runs early
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // We only want to track actual page views, not API calls for data
        // This is a basic filter, more sophisticated logic might be needed
        if (str_starts_with($route, 'api_')) {
            return;
        }

        $this->analyticsService->trackPageView($route ?? $request->getPathInfo());
    }
}
