<?php

declare(strict_types=1);

namespace CoreBundle\Repository;

use CoreBundle\Entity\Notification;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return list<Notification>
     */
    public function findRecentFor(User $user, int $limit = 30): array
    {
        return $this->createQueryBuilder('notification')
            ->leftJoin('notification.actor', 'actor')->addSelect('actor')
            ->where('IDENTITY(notification.recipient) = :recipientId')
            ->setParameter('recipientId', $user->getId(), UuidType::NAME)
            ->orderBy('notification.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findForRecipientById(User $user, string $id): ?Notification
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('notification')
            ->where('notification.id = :id')
            ->andWhere('IDENTITY(notification.recipient) = :recipientId')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('recipientId', $user->getId(), UuidType::NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

