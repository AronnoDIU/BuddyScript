<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Validation\AuthValidator;
use CoreBundle\Entity\User;
use CoreBundle\Service\Auth as AuthService;
use CoreBundle\Service\Auth\TwoFactorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends BaseController
{
    private readonly AuthService $authService;

    private readonly AuthValidator $authValidator;

    private readonly TwoFactorService $twoFactorService;

    public function __construct(
        AuthService $authService,
        AuthValidator $authValidator,
        TwoFactorService $twoFactorService,
    ) {
        parent::__construct();
        $this->authService = $authService;
        $this->authValidator = $authValidator;
        $this->twoFactorService = $twoFactorService;
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = $this->authService->extractPayload($request);

        try {
            $this->authValidator
                ->setAction('register')
                ->validate($payload);
        } catch (ValidationException $e) {
            return $this->json(['errors' => $e->getErrors()], 422);
        }

        try {
            $result = $this->authService->register($payload);
        } catch (\DomainException $e) {
            return $this->json(['message' => $e->getMessage()], 409);
        }

        return $this->json($result, 201);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json($this->authService->me($user));
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshTokenManager = $this->authService->getRefreshTokenManager();
        $refreshToken = (string) $request->cookies->get($refreshTokenManager->getCookieName(), '');
        if ($refreshToken === '') {
            return $this->refreshFailureResponse($request, 'Refresh token is missing.');
        }

        $result = $this->authService->refresh($refreshToken);
        if ($result === null) {
            return $this->refreshFailureResponse($request, 'Refresh token is invalid or expired.');
        }

        $response = $this->json([
            'token' => $result['token'],
            'user' => $result['user'],
        ]);

        $response->headers->setCookie($refreshTokenManager->createCookie($result['refreshToken'], $request->isSecure()));

        return $response;
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $refreshTokenManager = $this->authService->getRefreshTokenManager();
        $refreshToken = (string) $request->cookies->get($refreshTokenManager->getCookieName(), '');
        $this->authService->logout($refreshToken);

        $response = $this->json(['message' => 'Logged out successfully.']);
        $response->headers->setCookie($refreshTokenManager->createClearedCookie($request->isSecure()));

        return $response;
    }

    #[Route('/2fa/status', name: 'api_auth_2fa_status', methods: ['GET'])]
    public function twoFactorStatus(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json([
            'twoFactorEnabled' => $user->isTwoFactorEnabled(),
            'hasPendingSetup' => $user->getTwoFactorPendingSecret() !== null,
        ]);
    }

    #[Route('/2fa/setup/init', name: 'api_auth_2fa_setup_init', methods: ['POST'])]
    public function initTwoFactorSetup(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $result = $this->twoFactorService->beginSetup($user);

        return $this->json([
            'message' => 'Two-factor setup initialized.',
            'secret' => $result['secret'],
            'otpauthUri' => $result['otpauthUri'],
        ]);
    }

    #[Route('/2fa/setup/confirm', name: 'api_auth_2fa_setup_confirm', methods: ['POST'])]
    public function confirmTwoFactorSetup(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->authService->extractPayload($request);
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            return $this->json(['message' => 'Verification code is required.'], 422);
        }

        if (!$this->twoFactorService->confirmSetup($user, $code)) {
            return $this->json(['message' => 'Invalid verification code.'], 422);
        }

        return $this->json(['message' => 'Two-factor authentication enabled successfully.']);
    }

    #[Route('/2fa/disable', name: 'api_auth_2fa_disable', methods: ['POST'])]
    public function disableTwoFactor(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $this->authService->extractPayload($request);
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            return $this->json(['message' => 'Verification code is required.'], 422);
        }

        if (!$this->twoFactorService->disable($user, $code)) {
            return $this->json(['message' => 'Invalid verification code.'], 422);
        }

        return $this->json(['message' => 'Two-factor authentication disabled successfully.']);
    }

    #[Route('/2fa/verify', name: 'api_auth_2fa_verify', methods: ['POST'])]
    public function verifyTwoFactorLogin(Request $request): JsonResponse
    {
        $payload = $this->authService->extractPayload($request);
        $challengeId = trim((string) ($payload['challengeId'] ?? ''));
        $code = trim((string) ($payload['code'] ?? ''));

        if ($challengeId === '' || $code === '') {
            return $this->json(['message' => 'challengeId and code are required.'], 422);
        }

        $user = $this->twoFactorService->verifyLoginChallenge($challengeId, $code);
        if ($user === null) {
            return $this->json(['message' => 'Invalid or expired two-factor challenge.'], 401);
        }

        $result = $this->authService->issueAuthTokens($user);
        $response = $this->json([
            'token' => $result['token'],
            'user' => $result['user'],
        ]);
        $response->headers->setCookie($this->authService->getRefreshTokenManager()->createCookie($result['refreshToken'], $request->isSecure()));

        return $response;
    }

    private function refreshFailureResponse(Request $request, string $message): JsonResponse
    {
        $refreshTokenManager = $this->authService->getRefreshTokenManager();
        $response = $this->json(['message' => $message], 401);
        $response->headers->setCookie($refreshTokenManager->createClearedCookie($request->isSecure()));

        return $response;
    }
}
