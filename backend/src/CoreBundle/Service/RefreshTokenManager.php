<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Auth\RefreshToken;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Auth\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

readonly class RefreshTokenManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%env(int:REFRESH_TOKEN_TTL)%')]
        private int $refreshTokenTtl,
        #[Autowire('%env(REFRESH_TOKEN_COOKIE_NAME)%')]
        private string $refreshTokenCookieName,
    ) {
    }

    public function issueForUser(User $user): string
    {
        $plainToken = $this->generatePlainToken();

        $refreshToken = (new RefreshToken())
            ->setUser($user)
            ->setTokenHash($this->hashToken($plainToken))
            ->setExpiresAt((new \DateTimeImmutable())->modify(sprintf('+%d seconds', $this->refreshTokenTtl)));

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $plainToken;
    }

    /**
     * @return array{user: User, refreshToken: string}|null
     */
    public function rotate(string $plainToken): ?array
    {
        $now = new \DateTimeImmutable();
        $currentToken = $this->getRefreshTokenRepository()->findActiveByTokenHash($this->hashToken($plainToken), $now);

        if ($currentToken === null) {
            return null;
        }

        $newPlainToken = $this->generatePlainToken();

        $currentToken->setRevokedAt($now);

        $newToken = (new RefreshToken())
            ->setUser($currentToken->getUser())
            ->setTokenHash($this->hashToken($newPlainToken))
            ->setExpiresAt($now->modify(sprintf('+%d seconds', $this->refreshTokenTtl)));

        $this->entityManager->persist($newToken);
        $this->entityManager->flush();

        return [
            'user' => $currentToken->getUser(),
            'refreshToken' => $newPlainToken,
        ];
    }

    public function revokeByPlainToken(string $plainToken): void
    {
        $token = $this->getRefreshTokenRepository()->findOneByTokenHash($this->hashToken($plainToken));
        if ($token === null || $token->getRevokedAt() !== null) {
            return;
        }

        $token->setRevokedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function createCookie(string $plainToken, bool $isSecureRequest): Cookie
    {
        return Cookie::create($this->refreshTokenCookieName, $plainToken)
            ->withHttpOnly(true)
            ->withSecure($isSecureRequest)
            ->withSameSite(Cookie::SAMESITE_LAX)
            ->withPath('/api')
            ->withExpires((new \DateTimeImmutable())->modify(sprintf('+%d seconds', $this->refreshTokenTtl)));
    }

    public function createClearedCookie(bool $isSecureRequest): Cookie
    {
        return Cookie::create($this->refreshTokenCookieName)
            ->withValue('')
            ->withHttpOnly(true)
            ->withSecure($isSecureRequest)
            ->withSameSite(Cookie::SAMESITE_LAX)
            ->withPath('/api')
            ->withExpires(new \DateTimeImmutable('-1 day'));
    }

    public function getCookieName(): string
    {
        return $this->refreshTokenCookieName;
    }

    private function generatePlainToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    private function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function getRefreshTokenRepository(): RefreshTokenRepository
    {
        $repository = $this->entityManager->getRepository(RefreshToken::class);
        if (!$repository instanceof RefreshTokenRepository) {
            throw new \LogicException('Refresh token repository is not configured correctly.');
        }

        return $repository;
    }
}

