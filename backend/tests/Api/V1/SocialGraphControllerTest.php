<?php

declare(strict_types=1);

namespace App\Tests\Api\V1;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SocialGraphControllerTest extends ApiTestCase
{
    public function testFriendRequestLifecycle(): void
    {
        $sender = $this->createUser('sender@example.test');
        $receiver = $this->createUser('receiver@example.test');

        $senderClient = $this->authClientForUser($sender);
        $senderClient->request('POST', '/api/v1/social/requests', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetUserId' => $receiver->getId()->toRfc4122(),
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $payload = json_decode((string) $senderClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pending', $payload['connection']['status'] ?? null);

        $receiverClient = $this->authClientForUser($receiver);
        $receiverClient->request('GET', '/api/v1/social/overview');
        self::assertResponseIsSuccessful();
        $overview = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $overview['incomingRequests'] ?? []);

        $connectionId = $overview['incomingRequests'][0]['id'];
        $receiverClient->request('POST', sprintf('/api/v1/social/requests/%s/respond', $connectionId), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'status' => 'accepted',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $receiverClient->request('GET', '/api/v1/social/overview');
        self::assertResponseIsSuccessful();
        $updatedOverview = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $updatedOverview['friends'] ?? []);
    }

    public function testOverviewRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/social/overview');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRespondRequestRejectsInvalidPayload(): void
    {
        [$client] = $this->createAuthenticatedClient('social_invalid');

        $client->request('POST', '/api/v1/social/requests/not-a-real-id/respond', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'status' => 'unknown-status',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRespondRequestReturnsNotFoundForUnknownConnection(): void
    {
        [$client] = $this->createAuthenticatedClient('social_missing');

        $client->request('POST', '/api/v1/social/requests/00000000-0000-0000-0000-000000000001/respond', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'status' => 'accepted',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}

