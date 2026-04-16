<?php

declare(strict_types=1);

namespace App\Tests\Api\V1\Safety;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TrustSafetyControllerTest extends ApiTestCase
{
    public function testCreateReportAndListMyReports(): void
    {
        [$client] = $this->createAuthenticatedClient('safety_reporter');

        $client->request('POST', '/api/v1/safety/reports', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetType' => 'post',
            'targetId' => 'abc-123',
            'category' => 'abuse',
            'reason' => 'Spam and repeated harassment.',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $createPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('post', $createPayload['report']['targetType'] ?? null);

        $client->request('GET', '/api/v1/safety/reports/me');
        self::assertResponseIsSuccessful();
        $listPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $listPayload['reports'] ?? []);
    }

    public function testCreateReportRejectsMissingRequiredFields(): void
    {
        [$client] = $this->createAuthenticatedClient('safety_missing');

        $client->request('POST', '/api/v1/safety/reports', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetType' => 'post',
            'reason' => 'missing fields',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testBlockUnblockAndListBlockedUsers(): void
    {
        $blocker = $this->createUser('blocker@example.test');
        $blocked = $this->createUser('blocked@example.test');

        $client = $this->authClientForUser($blocker);

        $client->request('POST', sprintf('/api/v1/safety/blocks/%s', $blocked->getId()->toRfc4122()));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('GET', '/api/v1/safety/blocks');
        self::assertResponseIsSuccessful();
        $blockedPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $blockedPayload['blockedUsers'] ?? []);

        $client->request('DELETE', sprintf('/api/v1/safety/blocks/%s', $blocked->getId()->toRfc4122()));
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/safety/blocks');
        self::assertResponseIsSuccessful();
        $afterUnblockPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(0, $afterUnblockPayload['blockedUsers'] ?? []);
    }

    public function testCannotBlockSelf(): void
    {
        $user = $this->createUser('self_block@example.test');
        $client = $this->authClientForUser($user);

        $client->request('POST', sprintf('/api/v1/safety/blocks/%s', $user->getId()->toRfc4122()));
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testBlockUnknownUserReturnsNotFound(): void
    {
        [$client] = $this->createAuthenticatedClient('safety_unknown');

        $client->request('POST', '/api/v1/safety/blocks/00000000-0000-0000-0000-000000000999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testBlockUserIsIdempotent(): void
    {
        $blocker = $this->createUser('idempotent_blocker@example.test');
        $blocked = $this->createUser('idempotent_blocked@example.test');
        $client = $this->authClientForUser($blocker);

        $client->request('POST', sprintf('/api/v1/safety/blocks/%s', $blocked->getId()->toRfc4122()));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('POST', sprintf('/api/v1/safety/blocks/%s', $blocked->getId()->toRfc4122()));
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $client->request('GET', '/api/v1/safety/blocks');
        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $payload['blockedUsers'] ?? []);
    }

    public function testSafetyEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/safety/blocks');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request('POST', '/api/v1/safety/reports', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'targetType' => 'post',
            'targetId' => 'x',
            'category' => 'spam',
            'reason' => 'unauthenticated',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}

