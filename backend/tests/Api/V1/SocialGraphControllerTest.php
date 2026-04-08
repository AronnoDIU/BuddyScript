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
}

