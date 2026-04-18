<?php

declare(strict_types=1);

namespace ApiBundle\Security;

use CoreBundle\Entity\User as UserEntity;
use CoreBundle\Service\Auth as AuthService;
use CoreBundle\Service\Auth\TwoFactorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

readonly class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private AuthService      $authService,
        private TwoFactorService $twoFactorService,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if (!$user instanceof UserEntity) {
            return new JsonResponse(['message' => 'Authentication failed.'], 401);
        }

        if ($user->isTwoFactorEnabled()) {
            $challenge = $this->twoFactorService->createLoginChallenge($user);

            return new JsonResponse([
                'twoFactorRequired' => true,
                'challengeId' => $challenge->getId()->toRfc4122(),
                'expiresAt' => $challenge->getExpiresAt()->format(DATE_ATOM),
                'message' => 'Two-factor verification required.',
            ], 202);
        }

        $result = $this->authService->issueAuthTokens($user);

        $response = new JsonResponse([
            'token' => $result['token'],
            'user' => $result['user'],
        ]);

        $response->headers->setCookie($this->authService->getRefreshTokenManager()->createCookie($result['refreshToken'], $request->isSecure()));

        return $response;
    }
}
