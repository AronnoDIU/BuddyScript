<?php

declare(strict_types=1);

namespace CoreBundle\Service\Auth;

use CoreBundle\Entity\Auth\TwoFactorChallenge;
use CoreBundle\Entity\User;
use CoreBundle\Repository\Auth\TwoFactorChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;

readonly class TwoFactorService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function beginSetup(User $user): array
    {
        $secret = $this->generateSecret();
        $user->setTwoFactorPendingSecret($secret);
        $this->entityManager->flush();

        return [
            'secret' => $secret,
            'otpauthUri' => $this->buildOtpAuthUri($user, $secret),
        ];
    }

    public function confirmSetup(User $user, string $code): bool
    {
        $pending = $user->getTwoFactorPendingSecret();
        if ($pending === null || $pending === '') {
            return false;
        }

        if (!$this->verifyTotpCode($pending, $code)) {
            return false;
        }

        $user
            ->setTwoFactorSecret($pending)
            ->setTwoFactorPendingSecret(null)
            ->setTwoFactorEnabled(true);

        $this->entityManager->flush();

        return true;
    }

    public function disable(User $user, string $code): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return true;
        }

        $secret = $user->getTwoFactorSecret();
        if ($secret === null || !$this->verifyTotpCode($secret, $code)) {
            return false;
        }

        $user
            ->setTwoFactorEnabled(false)
            ->setTwoFactorSecret(null)
            ->setTwoFactorPendingSecret(null);

        $this->entityManager->flush();

        return true;
    }

    public function createLoginChallenge(User $user): TwoFactorChallenge
    {
        try {
            $challenge = new TwoFactorChallenge()
                ->setUser($user)
                ->setPurpose('login')
                ->setExpiresAt(new \DateTimeImmutable()->modify('+5 minutes'));
        } catch (\Exception $e) {
                throw new \LogicException('Failed to create two-factor challenge due to invalid date configuration.', 0, $e);
        }

        $this->entityManager->persist($challenge);
        $this->entityManager->flush();

        return $challenge;
    }

    public function verifyLoginChallenge(string $challengeId, string $code): ?User
    {
        $challenge = $this->getChallengeRepository()->findActiveById($challengeId, new \DateTimeImmutable());
        if (!$challenge instanceof TwoFactorChallenge) {
            return null;
        }

        $user = $challenge->getUser();
        $secret = $user->getTwoFactorSecret();
        if ($secret === null || !$this->verifyTotpCode($secret, $code)) {
            return null;
        }

        $challenge->markConsumed();
        $this->entityManager->flush();

        return $user;
    }

    public function verifyTotpForUser(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if ($secret === null || $secret === '') {
            return false;
        }

        return $this->verifyTotpCode($secret, $code);
    }

    private function verifyTotpCode(string $base32Secret, string $code): bool
    {
        $normalizedCode = preg_replace('/\s+/', '', trim($code));
        if (!preg_match('/^\d{6}$/', (string) $normalizedCode)) {
            return false;
        }

        $now = time();
        foreach ([-1, 0, 1] as $windowOffset) {
            $counter = (int) floor(($now + ($windowOffset * 30)) / 30);
            $expected = $this->generateTotpCode($base32Secret, $counter);
            if (hash_equals($expected, (string) $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    private function generateTotpCode(string $base32Secret, int $counter): string
    {
        $secret = $this->base32Decode($base32Secret);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $secret, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        $otp = $truncated % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function generateSecret(): string
    {
        try {
            return $this->base32Encode(random_bytes(20));
        } catch (RandomException $e) {
            throw new \RuntimeException('Failed to generate a secure random secret for two-factor authentication.', 0, $e);
        }
    }

    private function buildOtpAuthUri(User $user, string $secret): string
    {
        $label = rawurlencode(sprintf('BuddyScript:%s', $user->getEmail()));
        $issuer = rawurlencode('BuddyScript');

        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s&period=30&digits=6', $label, $secret, $issuer);
    }

    private function base32Encode(string $binary): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= $char
                    |> ord(...)
                    |> decbin(...)
                    |> (static fn($x) => str_pad($x, 8, '0', STR_PAD_LEFT));
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32) ?? '');
        $bits = '';
        foreach (str_split($clean) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) !== 8) {
                continue;
            }
            $binary .= chr(bindec($chunk));
        }

        return $binary;
    }

    private function getChallengeRepository(): TwoFactorChallengeRepository
    {
        $repository = $this->entityManager->getRepository(TwoFactorChallenge::class);
        if (!$repository instanceof TwoFactorChallengeRepository) {
            throw new \LogicException('Two-factor challenge repository is not configured correctly.');
        }

        return $repository;
    }
}
