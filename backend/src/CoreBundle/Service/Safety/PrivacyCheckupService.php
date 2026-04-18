<?php

declare(strict_types=1);

namespace CoreBundle\Service\Safety;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class PrivacyCheckupService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function checkup(User $user): array
    {
        $settings = $user->getPrivacySettings();

        return [
            'settings' => $settings,
            'checklist' => [
                [
                    'key' => 'profileVisibility',
                    'title' => 'Profile visibility',
                    'status' => $settings['profileVisibility'] === 'public' ? 'review' : 'ok',
                    'recommendation' => 'Consider limiting profile visibility to connections if you prefer a tighter audience.',
                ],
                [
                    'key' => 'allowMessagesFrom',
                    'title' => 'Message controls',
                    'status' => $settings['allowMessagesFrom'] === 'everyone' ? 'review' : 'ok',
                    'recommendation' => 'Limit messages to connections to reduce unsolicited conversations.',
                ],
                [
                    'key' => 'shareActivityStatus',
                    'title' => 'Activity status',
                    'status' => $settings['shareActivityStatus'] ? 'review' : 'ok',
                    'recommendation' => 'Disable activity status if you do not want live presence indicators.',
                ],
                [
                    'key' => 'twoFactor',
                    'title' => 'Two-factor authentication',
                    'status' => $user->isTwoFactorEnabled() ? 'ok' : 'warning',
                    'recommendation' => 'Enable 2FA for stronger account protection.',
                ],
            ],
            'security' => [
                'twoFactorEnabled' => $user->isTwoFactorEnabled(),
                'accountCreatedAt' => $user->getCreatedAt()->format(DATE_ATOM),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function update(User $user, array $settings): array
    {
        $allowed = [
            'profileVisibility' => ['public', 'connections', 'private'],
            'discoverability' => ['everyone', 'connections', 'nobody'],
            'allowMessagesFrom' => ['everyone', 'connections', 'nobody'],
            'shareActivityStatus' => [true, false],
            'adPersonalization' => [true, false],
        ];

        $current = $user->getPrivacySettings();
        $next = $current;

        foreach ($allowed as $key => $choices) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            $value = $settings[$key];
            if (!in_array($value, $choices, true)) {
                throw new \InvalidArgumentException(sprintf('Invalid value for %s.', $key));
            }

            $next[$key] = $value;
        }

        $user->setPrivacySettings($next);
        $this->entityManager->flush();

        return $this->checkup($user);
    }
}
