<?php

declare(strict_types=1);

namespace ApiBundle\Controller\Marketplace;

use ApiBundle\Controller\BaseController;
use CoreBundle\Entity\Marketplace\Listing;
use CoreBundle\Entity\User;
use CoreBundle\Service\Marketplace\MarketplaceService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class MarketplaceController extends BaseController
{
    public function __construct(
        private readonly MarketplaceService $marketplaceService,
    ) {
        parent::__construct();
    }

    #[Route('/marketplace/listings', name: 'api_marketplace_listings_list', methods: ['GET'])]
    public function listListings(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $query = (string) $request->query->get('q', '');
        $category = trim((string) $request->query->get('category', ''));
        $limit = (int) $request->query->get('limit', 20);
        $offset = (int) $request->query->get('offset', 0);

        return $this->json($this->marketplaceService->list($query, $category !== '' ? $category : null, $limit, $offset));
    }

    #[Route('/marketplace/my/listings', name: 'api_marketplace_my_listings', methods: ['GET'])]
    public function myListings(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->marketplaceService->myListings($user));
    }

    #[Route('/marketplace/listings', name: 'api_marketplace_listings_create', methods: ['POST'])]
    public function createListing(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ($title === '' || $description === '') {
            return $this->json(['message' => 'Title and description are required.'], 422);
        }

        try {
            $result = $this->marketplaceService->create(
                $user,
                $payload,
                $request->files->get('image') instanceof UploadedFile ? $request->files->get('image') : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json($result, 201);
    }

    #[Route('/marketplace/listings/{id}', name: 'api_marketplace_listings_get', methods: ['GET'])]
    public function getListing(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $result = $this->marketplaceService->getById($id);
        if ($result === null) {
            return $this->json(['message' => 'Listing not found.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/marketplace/listings/{id}', name: 'api_marketplace_listings_update', methods: ['PUT'])]
    public function updateListing(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->combineRequestData($request);

        try {
            $result = $this->marketplaceService->update(
                $user,
                $id,
                $payload,
                $request->files->get('image') instanceof UploadedFile ? $request->files->get('image') : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        if ($result === null) {
            return $this->json(['message' => 'Listing not found or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/marketplace/listings/{id}/mark-sold', name: 'api_marketplace_listings_mark_sold', methods: ['POST'])]
    public function markSold(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $result = $this->marketplaceService->markSold($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Listing not found or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }

    #[Route('/marketplace/listings/{id}', name: 'api_marketplace_listings_delete', methods: ['DELETE'])]
    public function deleteListing(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $result = $this->marketplaceService->delete($user, $id);
        if ($result === null) {
            return $this->json(['message' => 'Listing not found or insufficient permissions.'], 404);
        }

        return $this->json($result);
    }
}

