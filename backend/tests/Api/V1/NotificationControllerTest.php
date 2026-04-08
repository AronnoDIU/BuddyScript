<?php

declare(strict_types=1);

namespace App\Tests\Api\V1;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class NotificationControllerTest extends ApiTestCase
{
    public function testUnreadAndMarkReadFlow(): void
    {
        $sender = $this->createUser('notify_sender@example.test');
        $receiver = $this->createUser('notify_receiver@example.test');

        $senderClient = $this->authClientForUser($sender);
        $senderClient->request('POST', '/api/v1/social/requests', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetUserId' => $receiver->getId()->toRfc4122(),
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $receiverClient = $this->authClientForUser($receiver);
        $receiverClient->request('GET', '/api/v1/notifications');
        self::assertResponseIsSuccessful();

        $listPayload = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertGreaterThan(0, $listPayload['unreadCount'] ?? 0);
        self::assertNotEmpty($listPayload['notifications']);

        $notificationId = $listPayload['notifications'][0]['id'];

        $receiverClient->request('POST', sprintf('/api/v1/notifications/%s/read', $notificationId));
        self::assertResponseIsSuccessful();

        $receiverClient->request('GET', '/api/v1/notifications');
        self::assertResponseIsSuccessful();
        $updatedPayload = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $updatedPayload['unreadCount'] ?? 0);
    }
}

