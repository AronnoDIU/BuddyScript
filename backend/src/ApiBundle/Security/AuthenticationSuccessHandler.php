<?php

declare(strict_types=1);

namespace ApiBundle\Security;

use CoreBundle\Entity\User as UserEntity;
use CoreBundle\Service\ApiFormatter;
use CoreBundle\Service\RefreshTokenManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly RefreshTokenManager $refreshTokenManager,
        private readonly ApiFormatter $formatter,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if (!$user instanceof UserEntity) {
            return new JsonResponse(['message' => 'Authentication failed.'], 401);
        }

        $accessToken = $this->jwtTokenManager->create($user);
        $refreshToken = $this->refreshTokenManager->issueForUser($user);

        $response = new JsonResponse([
            'token' => $accessToken,
            'user' => $this->formatter->user($user),
        ]);

        $response->headers->setCookie($this->refreshTokenManager->createCookie($refreshToken, $request->isSecure()));

        return $response;
    }
}
