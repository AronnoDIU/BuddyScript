<?php

namespace CoreBundle\Service;

use CoreBundle\Entity\User;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class Auth
{
    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private ApiFormatter $formatter;

    private JWTTokenManagerInterface $jwtTokenManager;

    private RefreshTokenManager $refreshTokenManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ApiFormatter $formatter,
        JWTTokenManagerInterface $jwtTokenManager,
        RefreshTokenManager $refreshTokenManager,
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->formatter = $formatter;
        $this->jwtTokenManager = $jwtTokenManager;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    /**
     * @return array<string, mixed>
     */
    public function register(array $payload): array
    {
        $email = mb_strtolower(trim((string) $payload['email']));
        if ($this->getUserRepository()->findOneBy(['email' => $email]) !== null) {
            throw new \DomainException('Email is already in use.');
        }

        $user = new User();
        $user
            ->setFirstName((string) $payload['firstName'])
            ->setLastName((string) $payload['lastName'])
            ->setEmail($email)
            ->setPassword($this->passwordHasher->hashPassword($user, (string) $payload['password']));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return [
            'message' => 'Registration successful.',
            'user' => $this->formatter->user($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        return ['user' => $this->formatter->user($user)];
    }

    /**
     * @return array{token:string,user:array<string,mixed>,refreshToken:string}|null
     */
    public function refresh(string $refreshToken): ?array
    {
        $rotatedToken = $this->refreshTokenManager->rotate($refreshToken);
        if ($rotatedToken === null) {
            return null;
        }

        $user = $rotatedToken['user'];

        return [
            'token' => $this->jwtTokenManager->create($user),
            'user' => $this->formatter->user($user),
            'refreshToken' => $rotatedToken['refreshToken'],
        ];
    }

    public function logout(string $refreshToken): void
    {
        if ($refreshToken === '') {
            return;
        }

        $this->refreshTokenManager->revokeByPlainToken($refreshToken);
    }

    /**
     * @return array{token:string,user:array<string,mixed>,refreshToken:string}
     */
    public function issueAuthTokens(User $user): array
    {
        return [
            'token' => $this->jwtTokenManager->create($user),
            'user' => $this->formatter->user($user),
            'refreshToken' => $this->refreshTokenManager->issueForUser($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function extractPayload(Request $request): array
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
        } catch (\Exception) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function getRefreshTokenManager(): RefreshTokenManager
    {
        return $this->refreshTokenManager;
    }

    private function getUserRepository(): UserRepository
    {
        $repository = $this->entityManager->getRepository(User::class);
        if (!$repository instanceof UserRepository) {
            throw new \LogicException('User repository is not configured correctly.');
        }

        return $repository;
    }
}
