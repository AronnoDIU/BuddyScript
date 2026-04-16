<?php

declare(strict_types=1);

namespace App\Tests\Api\V1\Marketplace;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MarketplaceControllerTest extends ApiTestCase
{
    public function testListingsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/marketplace/listings');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateListGetUpdateMarkSoldDeleteLifecycle(): void
    {
        [$client] = $this->createAuthenticatedClient('market_lifecycle');

        $client->request('POST', '/api/v1/marketplace/listings', [
            'title' => 'MacBook Pro M3',
            'description' => 'Almost new, 16GB RAM',
            'priceAmount' => 2400,
            'currency' => 'USD',
            'category' => 'electronics',
            'conditionType' => 'used',
            'location' => 'Dhaka',
            'tags' => 'laptop,apple,m3',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $createPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $listingId = (string) ($createPayload['listing']['id'] ?? '');
        self::assertNotSame('', $listingId);
        self::assertSame('MacBook Pro M3', $createPayload['listing']['title'] ?? null);

        $client->request('GET', '/api/v1/marketplace/listings?q=macbook&category=electronics');
        self::assertResponseIsSuccessful();
        $listPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($listPayload['listings'] ?? []);

        $client->request('GET', '/api/v1/marketplace/my/listings');
        self::assertResponseIsSuccessful();
        $minePayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $minePayload['listings'] ?? []);

        $client->request('GET', sprintf('/api/v1/marketplace/listings/%s', $listingId));
        self::assertResponseIsSuccessful();
        $singlePayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($listingId, $singlePayload['listing']['id'] ?? null);

        $client->request('PUT', sprintf('/api/v1/marketplace/listings/%s', $listingId), [
            'priceAmount' => 2200,
            'status' => 'active',
            'location' => 'Chattogram',
        ]);
        self::assertResponseIsSuccessful();
        $updatePayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2200, $updatePayload['listing']['priceAmount'] ?? null);
        self::assertSame('Chattogram', $updatePayload['listing']['location'] ?? null);

        $client->request('POST', sprintf('/api/v1/marketplace/listings/%s/mark-sold', $listingId));
        self::assertResponseIsSuccessful();
        $soldPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('sold', $soldPayload['listing']['status'] ?? null);

        $client->request('DELETE', sprintf('/api/v1/marketplace/listings/%s', $listingId));
        self::assertResponseIsSuccessful();

        $client->request('GET', sprintf('/api/v1/marketplace/listings/%s', $listingId));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSellerOnlyCanMutateOwnListing(): void
    {
        [$ownerClient] = $this->createAuthenticatedClient('market_owner');

        $ownerClient->request('POST', '/api/v1/marketplace/listings', [
            'title' => 'Gaming Chair',
            'description' => 'Good condition',
            'priceAmount' => 140,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $payload = json_decode((string) $ownerClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $listingId = (string) ($payload['listing']['id'] ?? '');
        self::assertNotSame('', $listingId);

        [$otherClient] = $this->createAuthenticatedClient('market_other');

        $otherClient->request('PUT', sprintf('/api/v1/marketplace/listings/%s', $listingId), [
            'priceAmount' => 20,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $otherClient->request('POST', sprintf('/api/v1/marketplace/listings/%s/mark-sold', $listingId));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $otherClient->request('DELETE', sprintf('/api/v1/marketplace/listings/%s', $listingId));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateRejectsMissingTitleDescription(): void
    {
        [$client] = $this->createAuthenticatedClient('market_invalid');

        $client->request('POST', '/api/v1/marketplace/listings', [
            'title' => '',
            'description' => '',
            'priceAmount' => 100,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateRejectsInvalidStatus(): void
    {
        [$client] = $this->createAuthenticatedClient('market_bad_status');

        $client->request('POST', '/api/v1/marketplace/listings', [
            'title' => 'Table',
            'description' => 'Wooden table',
            'priceAmount' => 50,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $listingId = (string) ($payload['listing']['id'] ?? '');

        $client->request('PUT', sprintf('/api/v1/marketplace/listings/%s', $listingId), [
            'status' => 'not-a-real-status',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}

