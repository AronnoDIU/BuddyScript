<?php

declare(strict_types=1);

namespace ApiBundle\Controller;

use CoreBundle\Entity\User;
use CoreBundle\Repository\UserRepository;
use CoreBundle\Service\ApiFormatter;
use CoreBundle\Service\RefreshTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly ApiFormatter $formatter,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly RefreshTokenManager $refreshTokenManager,
    ) {
        parent::__construct();
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = $this->extractPayload($request);

        $constraints = new Assert\Collection([
            'firstName' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(max: 120)])],
            'lastName' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(max: 120)])],
            'email' => [new Assert\Required([new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)])],
            'password' => [new Assert\Required([new Assert\NotBlank(), new Assert\Length(min: 8, max: 255)])],
        ]);

        $errors = $this->validator->validate($payload, $constraints);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 422);
        }

        if ($this->userRepository->findOneBy(['email' => mb_strtolower(trim((string) $payload['email']))]) !== null) {
            return $this->json(['message' => 'Email is already in use.'], 409);
        }

        $user = new User()
            ->setFirstName((string) $payload['firstName'])
            ->setLastName((string) $payload['lastName'])
            ->setEmail((string) $payload['email']);

        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $payload['password']));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Registration successful.',
            'user' => $this->formatter->user($user),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(Request $request): array
    {
        $payload = $request->request->all();
        if ($payload !== []) {
            return $payload;
        }

        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized.'], 401);
        }

        return $this->json(['user' => $this->formatter->user($user)]);
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = (string) $request->cookies->get($this->refreshTokenManager->getCookieName(), '');
        if ($refreshToken === '') {
            return $this->refreshFailureResponse($request, 'Refresh token is missing.');
        }

        $rotatedToken = $this->refreshTokenManager->rotate($refreshToken);
        if ($rotatedToken === null) {
            return $this->refreshFailureResponse($request, 'Refresh token is invalid or expired.');
        }

        $user = $rotatedToken['user'];
        $accessToken = $this->jwtTokenManager->create($user);

        $response = $this->json([
            'token' => $accessToken,
            'user' => $this->formatter->user($user),
        ]);

        $response->headers->setCookie($this->refreshTokenManager->createCookie(
            $rotatedToken['refreshToken'],
            $request->isSecure(),
        ));

        return $response;
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = (string) $request->cookies->get($this->refreshTokenManager->getCookieName(), '');
        if ($refreshToken !== '') {
            $this->refreshTokenManager->revokeByPlainToken($refreshToken);
        }

        $response = $this->json(['message' => 'Logged out successfully.']);
        $response->headers->setCookie($this->refreshTokenManager->createClearedCookie($request->isSecure()));

        return $response;
    }

    private function refreshFailureResponse(Request $request, string $message): JsonResponse
    {
        $response = $this->json(['message' => $message], 401);
        $response->headers->setCookie($this->refreshTokenManager->createClearedCookie($request->isSecure()));

        return $response;
    }
}
