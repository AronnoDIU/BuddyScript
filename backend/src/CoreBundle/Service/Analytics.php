<?php

namespace CoreBundle\Service;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Analytics extends BaseService
{
    private LoggerInterface $logger;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, LoggerInterface $logger, RequestStack $requestStack)
    {
        parent::__construct($entityManager, $tokenStorage);
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public function trackEvent(string $eventName, array $data = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request ? $request->getClientIp() : 'unknown';
        $userAgent = $request ? $request->headers->get('User-Agent') : 'unknown';
        $userId = null;

        // Attempt to get the current user if available
        $token = $this->requestStack->getSession()->get('_security_main'); // Assuming 'main' firewall
        if ($token && is_string($token)) {
            $decodedToken = json_decode(base64_decode($token), true);
            if (isset($decodedToken['user_id'])) {
                $userId = $decodedToken['user_id'];
            }
        }


        $logContext = array_merge([
            'event' => $eventName,
            'timestamp' => (new \DateTime())->format(\DateTimeInterface::ATOM),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'user_id' => $userId, // Add user ID to all events
        ], $data);

        $this->logger->info('ANALYTICS_EVENT', $logContext);
    }

    public function trackUserActivity(User $user, string $activityType, array $details = []): void
    {
        $this->trackEvent('user_activity', array_merge([
            'user_id' => $user->getId(),
            'activity_type' => $activityType,
        ], $details));
    }

    /**
     * Tracks a page view event.
     *
     * @param string $pageName The name or path of the page being viewed.
     * @param array $data Additional data related to the page view.
     */
    public function trackPageView(string $pageName, array $data = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $url = $request ? $request->getUri() : 'unknown';
        $referrer = $request ? $request->headers->get('referer') : 'unknown';

        $this->trackEvent('page_view', array_merge([
            'page_name' => $pageName,
            'url' => $url,
            'referrer' => $referrer,
        ], $data));
    }

    /**
     * Tracks a specific feature usage event.
     *
     * @param string $featureName The name of the feature being used (e.g., 'post_creation', 'message_sent').
     * @param array $data Additional data related to the feature usage.
     */
    public function trackFeatureUsage(string $featureName, array $data = []): void
    {
        $this->trackEvent('feature_usage', array_merge([
            'feature_name' => $featureName,
        ], $data));
    }

    // Placeholder for more advanced analytics methods
    public function getPopularItems(string $type, int $limit = 10): array
    {
        // This would typically involve querying aggregated data or a dedicated analytics store
        $this->logger->warning('Analytics: getPopularItems is a placeholder and needs implementation.');
        return [];
    }

    public function getTrendingTopics(int $limit = 10): array
    {
        $this->logger->warning('Analytics: getTrendingTopics is a placeholder and needs implementation.');
        return [];
    }
}
