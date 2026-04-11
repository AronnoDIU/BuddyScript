<?php

declare(strict_types=1);

namespace ApiBundle\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtTokenAuthenticator extends JWTAuthenticator
{
    private const string API_PREFIX = '/api/';
    private const string STREAM_ROUTE_SUFFIX = '/messenger/stream';

    public function supports(Request $request): ?bool
    {
        if (!str_starts_with($request->getPathInfo(), self::API_PREFIX)) {
            return false;
        }

        // EventSource cannot send Authorization headers in some clients.
        if ($this->canUseQueryToken($request)) {
            $token = trim((string) $request->query->get('streamToken', ''));
            if ($token !== '') {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return parent::supports($request);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Continue request flow and let the matched controller produce the response.
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => 'Authentication failed.'],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer']
        );
    }

    private function canUseQueryToken(Request $request): bool
    {
        if ($request->headers->has('Authorization')) {
            return false;
        }

        if ($request->getMethod() !== Request::METHOD_GET) {
            return false;
        }

        if (!str_ends_with($request->getPathInfo(), self::STREAM_ROUTE_SUFFIX)) {
            return false;
        }

        return $request->query->has('streamToken');
    }
}
