<?php

namespace ApiBundle\Controller;

use CoreBundle\Service\Analytics;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyticsController extends BaseController
{
    private Analytics $analyticsService;

    public function __construct(Analytics $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    #[Route('/analytics/page-view', name: 'api_analytics_page_view', methods: ['POST'])]
    public function trackPageView(Request $request): JsonResponse
    {
        $payload = $this->extractJsonPayload($request);
        $pageName = $payload['pageName'] ?? 'unknown';
        $data = $payload['data'] ?? [];

        $this->analyticsService->trackPageView($pageName, $data);

        return $this->apiResponse(['message' => 'Page view tracked successfully.'], Response::HTTP_ACCEPTED);
    }

    #[Route('/analytics/feature-usage', name: 'api_analytics_feature_usage', methods: ['POST'])]
    public function trackFeatureUsage(Request $request): JsonResponse
    {
        $payload = $this->extractJsonPayload($request);
        $featureName = $payload['featureName'] ?? 'unknown';
        $data = $payload['data'] ?? [];

        $this->analyticsService->trackFeatureUsage($featureName, $data);

        return $this->apiResponse(['message' => 'Feature usage tracked successfully.'], Response::HTTP_ACCEPTED);
    }
}
