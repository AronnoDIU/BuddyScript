<?php

declare(strict_types=1);

namespace App\Tests\Api\V1;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ReactionControllerTest extends ApiTestCase
{
    public function testReactToPostAndReadSummary(): void
    {
        [$client] = $this->createAuthenticatedClient('reactor');

        $client->request('POST', '/api/v1/posts', [
            'content' => 'Reaction test post',
            'visibility' => 'public',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $postPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $postId = $postPayload['post']['id'] ?? null;
        self::assertIsString($postId);

        $client->request('POST', '/api/v1/reactions/toggle', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetType' => 'post',
            'targetId' => $postId,
            'type' => 'love',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $togglePayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('love', $togglePayload['myReaction'] ?? null);

        $client->request('GET', sprintf('/api/v1/reactions?targetType=post&targetId=%s', $postId));
        self::assertResponseIsSuccessful();
        $summaryPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $summaryPayload['total'] ?? 0);
        self::assertSame(1, $summaryPayload['summary']['love'] ?? 0);
    }
}

