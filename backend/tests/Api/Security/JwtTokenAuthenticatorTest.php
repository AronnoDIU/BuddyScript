<?php

declare(strict_types=1);

namespace App\Tests\Api\Security;

use App\Tests\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class JwtTokenAuthenticatorTest extends ApiTestCase
{
    public function testApiRouteWithoutTokenReturnsUnauthorized(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/messenger/conversations');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testApiRouteWithAuthorizationHeaderIsAccepted(): void
    {
        [$client] = $this->createAuthenticatedClient('jwt_supports_api_header');
        $client->request('GET', '/api/v1/messenger/conversations');

        self::assertResponseIsSuccessful();
    }

    public function testInvalidBearerTokenReturnsFailureContract(): void
    {
        $client = static::createClient([], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_Authorization' => 'Bearer definitely.invalid.token',
        ]);
        $client->request('GET', '/api/v1/messenger/conversations');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame('Bearer', $client->getResponse()->headers->get('WWW-Authenticate'));

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame('Authentication failed.', $payload['error'] ?? null);
    }

    public function testStreamTokenWorksForGetStreamRouteWithoutAuthorizationHeader(): void
    {
        [$authClient] = $this->createAuthenticatedClient('jwt_streamtoken_get_stream');
        $authorization = (string) $authClient->getServerParameter('HTTP_Authorization');
        $streamToken = trim(str_replace('Bearer', '', $authorization));
        self::assertNotSame('', $streamToken);

        static::ensureKernelShutdown();
        $anonClient = static::createClient();
        $anonClient->request('GET', '/api/v1/messenger/stream?streamToken=' . urlencode($streamToken));

        self::assertResponseIsSuccessful();
    }

    public function testStreamTokenIsIgnoredOnNonStreamRoute(): void
    {
        [$authClient] = $this->createAuthenticatedClient('jwt_streamtoken_nonstream');
        $authClient->request('POST', '/api/v1/messenger/stream-token');
        self::assertResponseIsSuccessful();

        $tokenPayload = json_decode((string) $authClient->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $streamToken = (string) ($tokenPayload['token'] ?? '');
        self::assertNotSame('', $streamToken);

        static::ensureKernelShutdown();
        $anonClient = static::createClient();
        $anonClient->request('GET', '/api/v1/messenger/updates?streamToken=' . urlencode($streamToken));

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testExistingAuthorizationHeaderIsNotOverriddenByStreamToken(): void
    {
        [$validAuthClient] = $this->createAuthenticatedClient('jwt_header_priority');
        $validAuthClient->request('GET', '/api/v1/messenger/stream?streamToken=invalid.stream.token');

        self::assertResponseIsSuccessful();
    }
}

