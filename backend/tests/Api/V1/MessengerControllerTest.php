<?php

declare(strict_types=1);

namespace App\Tests\Api\V1;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class MessengerControllerTest extends ApiTestCase
{
    public function testConversationsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/messenger/conversations');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSendListReadAndAttachmentFlow(): void
    {
        $sender = $this->createUser('messenger_sender@example.test');
        $receiver = $this->createUser('messenger_receiver@example.test');

        $senderClient = $this->authClientForUser($sender);
        $senderClient->request('POST', '/api/v1/messenger/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $receiver->getId()->toRfc4122(),
            'content' => 'Hello from sender',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $sendPayload = json_decode((string) $senderClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $conversationId = (string) ($sendPayload['conversation']['id'] ?? '');
        self::assertNotSame('', $conversationId);

        $tmpFile = tempnam(sys_get_temp_dir(), 'msg_attach_');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, 'attachment-text');

        $senderClient->request('POST', '/api/v1/messenger/messages', [
            'conversationId' => $conversationId,
            'content' => 'Attachment message',
        ], [
            'attachment' => new UploadedFile($tmpFile, 'note.txt', 'text/plain', null, true),
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $receiverClient = $this->authClientForUser($receiver);
        $receiverClient->request('GET', '/api/v1/messenger/conversations?q=messenger&offset=0&limit=10');
        self::assertResponseIsSuccessful();
        $conversationPayload = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $conversationPayload['conversations'] ?? []);
        self::assertGreaterThan(0, $conversationPayload['conversations'][0]['unreadCount'] ?? 0);
        self::assertArrayHasKey('pagination', $conversationPayload);
        self::assertArrayHasKey('participants', $conversationPayload['conversations'][0]);
        self::assertNotEmpty($conversationPayload['conversations'][0]['participants']);
        self::assertArrayHasKey('avatarUrl', $conversationPayload['conversations'][0]['participants'][0]);
        self::assertIsString($conversationPayload['conversations'][0]['participants'][0]['avatarUrl']);

        $receiverClient->request('GET', sprintf('/api/v1/messenger/conversations/%s/messages?limit=1', $conversationId));
        self::assertResponseIsSuccessful();
        $messagesPayload = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertGreaterThanOrEqual(1, count($messagesPayload['messages'] ?? []));
        self::assertArrayHasKey('pagination', $messagesPayload);

        $receiverClient->request('POST', sprintf('/api/v1/messenger/conversations/%s/read', $conversationId));
        self::assertResponseIsSuccessful();

        $receiverClient->request('GET', '/api/v1/messenger/conversations');
        self::assertResponseIsSuccessful();
        $afterReadPayload = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $afterReadPayload['conversations'][0]['unreadCount'] ?? -1);

        $receiverClient->request('GET', sprintf('/api/v1/messenger/conversations/%s/messages?limit=5', $conversationId));
        self::assertResponseIsSuccessful();
        $afterReadMessagesPayload = json_decode((string) $receiverClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($afterReadMessagesPayload['messages']);
        $lastMessage = end($afterReadMessagesPayload['messages']);
        self::assertIsArray($lastMessage);
        self::assertArrayHasKey('deliveredBy', $lastMessage);
        self::assertArrayHasKey('readBy', $lastMessage);

        foreach (['deliveredBy', 'readBy'] as $receiptKey) {
            foreach ($lastMessage[$receiptKey] ?? [] as $receiptUser) {
                self::assertArrayHasKey('avatarUrl', $receiptUser);
                self::assertIsString($receiptUser['avatarUrl']);
            }
        }
    }

    public function testSendMessageRejectsMalformedContentPayload(): void
    {
        $sender = $this->createUser('messenger_type_sender@example.test');
        $receiver = $this->createUser('messenger_type_receiver@example.test');

        $senderClient = $this->authClientForUser($sender);
        try {
            $senderClient->request('POST', '/api/v1/messenger/messages', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'recipientId' => $receiver->getId()->toRfc4122(),
                'content' => ['unexpected' => 'array'],
            ], JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            self::fail('Test payload JSON is malformed: ' . $e->getMessage());
        }

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMessagesEndpointReturnsNotFoundForUnknownConversation(): void
    {
        [$client] = $this->createAuthenticatedClient('messenger_missing');
        $client->request('GET', '/api/v1/messenger/conversations/00000000-0000-0000-0000-000000000321/messages');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testStreamRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/messenger/stream');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testStreamTokenRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/messenger/stream-token');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testPinMuteArchiveFlow(): void
    {
        $sender = $this->createUser('messenger_pref_sender@example.test');
        $receiver = $this->createUser('messenger_pref_receiver@example.test');

        $senderClient = $this->authClientForUser($sender);
        $senderClient->request('POST', '/api/v1/messenger/messages', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'recipientId' => $receiver->getId()->toRfc4122(),
            'content' => 'Preference flow',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $payload = json_decode((string) $senderClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $conversationId = (string) ($payload['conversation']['id'] ?? '');
        self::assertNotSame('', $conversationId);

        $senderClient->request('POST', sprintf('/api/v1/messenger/conversations/%s/pin', $conversationId), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['pinned' => true], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $senderClient->request('POST', sprintf('/api/v1/messenger/conversations/%s/mute', $conversationId), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['minutes' => 30], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $senderClient->request('POST', sprintf('/api/v1/messenger/conversations/%s/archive', $conversationId), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['archived' => true], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $senderClient->request('GET', '/api/v1/messenger/conversations?includeArchived=1');
        self::assertResponseIsSuccessful();
        $conversationsPayload = json_decode((string) $senderClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $conversationsPayload['conversations'] ?? []);
        self::assertTrue((bool) ($conversationsPayload['conversations'][0]['isPinned'] ?? false));
        self::assertTrue((bool) ($conversationsPayload['conversations'][0]['isArchived'] ?? false));
    }
}

