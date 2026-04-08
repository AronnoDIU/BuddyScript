<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MessengerStreamToken
{
    private readonly string $secret;

    public function __construct(#[Autowire('%kernel.secret%')] string $appSecret)
    {
        $this->secret = hash('sha256', $appSecret . '|messenger-stream-token');
    }

    /** @return array{token:string,expiresAt:string} */
    public function issue(User $user, int $ttlSeconds = 120): array
    {
        $expiresAt = time() + max(30, min(300, $ttlSeconds));
        $payload = [
            'sub' => $user->getId()->toRfc4122(),
            'exp' => $expiresAt,
            'scope' => 'messenger_stream',
        ];

        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        return [
            'token' => $encodedPayload . '.' . $signature,
            'expiresAt' => (new \DateTimeImmutable('@' . $expiresAt))->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM),
        ];
    }

    public function resolveUserId(string $token): ?string
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        if (!hash_equals($this->sign($encodedPayload), $signature)) {
            return null;
        }

        $decoded = $this->base64UrlDecode($encodedPayload);
        if ($decoded === null) {
            return null;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return null;
        }

        if (($payload['scope'] ?? null) !== 'messenger_stream') {
            return null;
        }

        $expiresAt = (int) ($payload['exp'] ?? 0);
        if ($expiresAt <= time()) {
            return null;
        }

        $subject = (string) ($payload['sub'] ?? '');
        if ($subject === '') {
            return null;
        }

        return $subject;
    }

    private function sign(string $encodedPayload): string
    {
        $raw = hash_hmac('sha256', $encodedPayload, $this->secret, true);

        return $this->base64UrlEncode($raw);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}

