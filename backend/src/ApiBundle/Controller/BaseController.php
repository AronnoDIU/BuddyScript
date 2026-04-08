<?php

namespace ApiBundle\Controller;

use ApiBundle\Service\Api as ApiService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends AbstractFOSRestController
{
    use ApiResponseTrait;

    protected ?ApiService $apiService;

    public function __construct(?ApiService $apiService = null)
    {
        $this->apiService = $apiService;
    }

    protected function getResponse($data): View
    {
        return $this->view($data);
    }

    protected function combineRequestData(Request $request): array
    {
        return array_merge($request->request->all(), $request->files->all());
    }

    protected function accessDeniedForAllBut($role): void
    {
        if ($this->apiService === null) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->apiService->isGranted($role)) {
            throw $this->createAccessDeniedException();
        }
    }

    protected function getEnv(): array|bool|float|int|string|\UnitEnum|null
    {
        return $this->getParameter('kernel.environment');
    }

    protected function serialize($data, array $groups): string
    {
        if ($this->apiService === null) {
            throw new \LogicException('Api service is not initialized on this controller.');
        }

        $serializer = $this->apiService->getSerializerService();
        $context = new SerializationContext();
        $context->setGroups($groups);
        $context->setSerializeNull(true);

        return $serializer->serialize(
            $data,
            'json',
            $context
        );
    }
}
