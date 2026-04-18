<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Notification as NotificationEntity;
use CoreBundle\Entity\User;
use CoreBundle\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class Notification
{
    private EntityManagerInterface $entityManager;

    private ApiFormatter $formatter;

    public function __construct(EntityManagerInterface $entityManager, ApiFormatter $formatter)
    {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
    }

    /**
     * @return array<string,mixed>
     */
    public function list(User $viewer, int $limit = 30): array
    {
        $notifications = $this->getNotificationRepository()->findRecentFor($viewer, $limit);

        return [
            'notifications' => array_map(fn (NotificationEntity $notification): array => $this->formatNotification($notification), $notifications),
            'unreadCount' => count(array_filter($notifications, static fn (NotificationEntity $notification): bool => !$notification->isRead())),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function markRead(User $viewer, string $notificationId): ?array
    {
        $notification = $this->getNotificationRepository()->findForRecipientById($viewer, $notificationId);
        if (!$notification instanceof NotificationEntity) {
            return null;
        }

        $notification->markAsRead();
        $this->entityManager->flush();

        return [
            'notification' => $this->formatNotification($notification),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function markAllRead(User $viewer): array
    {
        $notifications = $this->getNotificationRepository()->findRecentFor($viewer, 200);
        $updated = 0;

        foreach ($notifications as $notification) {
            if ($notification->isRead()) {
                continue;
            }

            $notification->markAsRead();
            $updated++;
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        return [
            'updated' => $updated,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function formatNotification(NotificationEntity $notification): array
    {
        return [
            'id' => $notification->getId()->toRfc4122(),
            'type' => $notification->getType(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format(DATE_ATOM),
            'readAt' => $notification->getReadAt()?->format(DATE_ATOM),
            'actor' => $notification->getActor() instanceof User ? $this->formatter->user($notification->getActor()) : null,
            'resource' => [
                'type' => $notification->getResourceType(),
                'id' => $notification->getResourceId()?->toRfc4122(),
            ],
            'data' => $notification->getData(),
        ];
    }

    private function getNotificationRepository(): NotificationRepository
    {
        $repository = $this->entityManager->getRepository(NotificationEntity::class);
        if (!$repository instanceof NotificationRepository) {
            throw new \LogicException('Notification repository is not configured correctly.');
        }

        return $repository;
    }
}
