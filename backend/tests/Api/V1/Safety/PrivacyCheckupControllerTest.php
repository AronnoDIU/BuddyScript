<?php

declare(strict_types=1);

namespace App\Tests\Api\V1\Safety;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class PrivacyCheckupControllerTest extends ApiTestCase
{
    public function testCheckupReturnsSettingsChecklistSecurity(): void
    {
        [$client] = $this->createAuthenticatedClient('privacy_read');

        $client->request('GET', '/api/v1/privacy-checkup');
        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('settings', $payload);
        self::assertArrayHasKey('checklist', $payload);
        self::assertArrayHasKey('security', $payload);
        self::assertIsArray($payload['checklist']);
    }

    public function testUpdateSettingsPartialPatchBehavior(): void
    {
        [$client] = $this->createAuthenticatedClient('privacy_update');

        $client->request('PUT', '/api/v1/privacy-checkup', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'profileVisibility' => 'connections',
            'allowMessagesFrom' => 'nobody',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseIsSuccessful();

        $updatePayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('connections', $updatePayload['settings']['profileVisibility'] ?? null);
        self::assertSame('nobody', $updatePayload['settings']['allowMessagesFrom'] ?? null);
        self::assertArrayHasKey('discoverability', $updatePayload['settings']);
    }

    public function testUpdateRejectsInvalidEnumAndBooleanValues(): void
    {
        [$client] = $this->createAuthenticatedClient('privacy_invalid');

        $client->request('PUT', '/api/v1/privacy-checkup', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'profileVisibility' => 'random-value',
            'adPersonalization' => 'not-bool',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateRejectsInvalidJsonPayload(): void
    {
        [$client] = $this->createAuthenticatedClient('privacy_bad_json');

        $client->request('PUT', '/api/v1/privacy-checkup', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{invalid-json}');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPrivacyCheckupRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/privacy-checkup');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request('PUT', '/api/v1/privacy-checkup', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['profileVisibility' => 'private'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}

