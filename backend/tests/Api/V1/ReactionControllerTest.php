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

    public function testCatalogRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/reactions/catalog');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testToggleReactionRejectsInvalidPayload(): void
    {
        [$client] = $this->createAuthenticatedClient('reaction_invalid');

        $client->request('POST', '/api/v1/reactions/toggle', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetType' => 'invalid-target',
            'targetId' => 'bad-id',
            'type' => 'invalid-type',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testBatchSummariesReturnsHydratedMap(): void
    {
        [$client] = $this->createAuthenticatedClient('reaction_batch');

        $client->request('POST', '/api/v1/posts', [
            'content' => 'Batch summary post',
            'visibility' => 'public',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $postPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $postId = (string) ($postPayload['post']['id'] ?? '');

        $client->request('POST', '/api/v1/reactions/toggle', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetType' => 'post',
            'targetId' => $postId,
            'type' => 'care',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/reactions/summaries', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targets' => [
                ['targetType' => 'post', 'targetId' => $postId],
            ],
        ], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('post:' . $postId, $payload['summaries'] ?? []);
        self::assertSame('care', $payload['summaries']['post:' . $postId]['myReaction'] ?? null);
    }

    public function testBatchSummariesRejectsInvalidTargetPayload(): void
    {
        [$client] = $this->createAuthenticatedClient('reaction_batch_invalid');

        $client->request('POST', '/api/v1/reactions/summaries', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targets' => [
                ['targetType' => 'invalid', 'targetId' => 'x'],
            ],
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testBatchSummariesRejectsUnexpectedTargetFields(): void
    {
        [$client] = $this->createAuthenticatedClient('reaction_batch_shape');

        $client->request('POST', '/api/v1/posts', [
            'content' => 'Batch shape post',
            'visibility' => 'public',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $postPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $postId = (string) ($postPayload['post']['id'] ?? '');

        $client->request('POST', '/api/v1/reactions/summaries', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targets' => [
                ['targetType' => 'post', 'targetId' => $postId, 'unexpected' => true],
            ],
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testBatchSummariesRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/reactions/summaries', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targets' => [],
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}

