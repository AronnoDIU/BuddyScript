<?php

declare(strict_types=1);

namespace App\Tests\Api\V1\Auth;

use App\Tests\Api\ApiTestCase;
use CoreBundle\Entity\Auth\TwoFactorChallenge;
use CoreBundle\Entity\User;
use CoreBundle\Service\Auth\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TwoFactorLoginChallengeTest extends ApiTestCase
{
    public function testLoginReturns202WhenTwoFactorEnabled(): void
    {
        $user = $this->createUserWithCredentials('2fa_login@example.test', 'Passw0rd!');
        $this->enableTwoFactorForUser($user, 'JBSWY3DPEHPK3PXP');

        $client = static::createClient();
        $client->request('POST', '/api/auth/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'identifier' => $user->getUsername(),
            'password' => 'Passw0rd!',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue((bool) ($payload['twoFactorRequired'] ?? false));
        self::assertIsString($payload['challengeId'] ?? null);
    }

    public function testVerifyChallengeReturnsTokenAndRefreshCookie(): void
    {
        $user = $this->createUserWithCredentials('2fa_verify_ok@example.test', 'Passw0rd!');
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->enableTwoFactorForUser($user, $secret);

        $challengeId = $this->createChallengeForUser($user);
        $code = $this->generateTotpCode($secret);

        $client = static::createClient();
        $client->request('POST', '/api/v1/2fa/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'challengeId' => $challengeId,
            'code' => $code,
        ], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($payload['token'] ?? null);
        self::assertSame($user->getEmail(), $payload['user']['email'] ?? null);

        $setCookie = $client->getResponse()->headers->get('set-cookie');
        self::assertIsString($setCookie);
        self::assertStringContainsString('refresh', strtolower($setCookie));
    }

    public function testVerifyChallengeCannotBeReused(): void
    {
        $user = $this->createUserWithCredentials('2fa_reuse@example.test', 'Passw0rd!');
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->enableTwoFactorForUser($user, $secret);

        $challengeId = $this->createChallengeForUser($user);
        $code = $this->generateTotpCode($secret);

        $client = static::createClient();
        $body = json_encode([
            'challengeId' => $challengeId,
            'code' => $code,
        ], JSON_THROW_ON_ERROR);

        $client->request('POST', '/api/v1/2fa/verify', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/2fa/verify', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testVerifyRejectsExpiredOrInvalidChallenge(): void
    {
        $user = $this->createUserWithCredentials('2fa_expired@example.test', 'Passw0rd!');
        $secret = 'JBSWY3DPEHPK3PXP';
        $this->enableTwoFactorForUser($user, $secret);

        $expiredId = $this->createChallengeForUser($user, new \DateTimeImmutable('-10 minutes'));

        $client = static::createClient();
        $client->request('POST', '/api/v1/2fa/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'challengeId' => $expiredId,
            'code' => $this->generateTotpCode($secret),
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request('POST', '/api/v1/2fa/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'challengeId' => '00000000-0000-0000-0000-000000000999',
            'code' => '123456',
        ], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testVerifyRequiresChallengeIdAndCode(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/2fa/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'challengeId' => '',
            'code' => '',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function createUserWithCredentials(string $email, string $plainPassword): User
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setFirstName('Two')
            ->setLastName('Factor')
            ->setEmail($email)
            ->setPassword('placeholder');

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function enableTwoFactorForUser(User $user, string $secret): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user
            ->setTwoFactorEnabled(true)
            ->setTwoFactorSecret($secret)
            ->setTwoFactorPendingSecret(null);
        $entityManager->flush();
    }

    private function createChallengeForUser(User $user, ?\DateTimeImmutable $expiresAt = null): string
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $twoFactorService = static::getContainer()->get(TwoFactorService::class);

        $challenge = $twoFactorService->createLoginChallenge($user);
        if ($expiresAt instanceof \DateTimeImmutable) {
            $challenge->setExpiresAt($expiresAt);
            $entityManager->flush();
        }

        return $challenge->getId()->toRfc4122();
    }

    private function generateTotpCode(string $base32Secret, ?int $timestamp = null): string
    {
        $time = $timestamp ?? time();
        $counter = (int) floor($time / 30);

        $secret = $this->base32Decode($base32Secret);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $secret, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        $otp = $truncated % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
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
}

