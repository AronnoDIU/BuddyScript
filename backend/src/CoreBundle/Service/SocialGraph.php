<?php

declare(strict_types=1);

namespace CoreBundle\Service;

use CoreBundle\Entity\Notification;
use CoreBundle\Entity\SocialGraph\Connection;
use CoreBundle\Entity\User;
use CoreBundle\Repository\SocialGraph\ConnectionRepository;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SocialGraph
{
    private readonly EntityManagerInterface $entityManager;

    private readonly ApiFormatter $formatter;

    public function __construct(EntityManagerInterface $entityManager, ApiFormatter $formatter)
    {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
    }

    /**
     * @return array<string,mixed>
     */
    public function overview(User $viewer): array
    {
        $incoming = $this->getConnectionRepository()->findIncomingPending($viewer);
        $outgoing = $this->getConnectionRepository()->findOutgoingPending($viewer);
        $friends = $this->getConnectionRepository()->findAccepted($viewer);

        return [
            'incomingRequests' => array_map(fn (Connection $connection): array => $this->formatConnection($viewer, $connection), $incoming),
            'outgoingRequests' => array_map(fn (Connection $connection): array => $this->formatConnection($viewer, $connection), $outgoing),
            'friends' => array_map(fn (Connection $connection): array => $this->formatConnection($viewer, $connection), $friends),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function sendRequest(User $requester, string $targetUserId, ?string $listKey = null): array
    {
        $target = $this->getUserRepository()->findOneById($targetUserId);
        if (!$target instanceof User) {
            throw new \DomainException('Target user was not found.');
        }

        if ($target->getId()->equals($requester->getId())) {
            throw new \DomainException('You cannot connect with yourself.');
        }

        $existing = $this->getConnectionRepository()->findBetweenUsers($requester, $target);
        if ($existing instanceof Connection) {
            throw new \DomainException('A connection already exists for these users.');
        }

        $connection = new Connection();
        $connection
            ->setRequester($requester)
            ->setAddressee($target)
            ->setStatus(Connection::STATUS_PENDING)
            ->setListKey($listKey);

        $notification = new Notification();
        $notification
            ->setRecipient($target)
            ->setActor($requester)
            ->setType('friend_request_received')
            ->setResourceType('connection')
            ->setResourceId($connection->getId())
            ->setData([
                'listKey' => $listKey,
            ]);

        $this->entityManager->persist($connection);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return [
            'connection' => $this->formatConnection($requester, $connection),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function respondToRequest(User $viewer, string $connectionId, string $status): ?array
    {
        $connection = $this->findConnectionById($connectionId);
        if (!$connection instanceof Connection) {
            return null;
        }

        if (!$connection->getAddressee()->getId()->equals($viewer->getId())) {
            throw new \DomainException('You are not allowed to respond to this request.');
        }

        if ($connection->getStatus() !== Connection::STATUS_PENDING) {
            throw new \DomainException('This request is already resolved.');
        }

        $connection->setStatus($status);

        $notification = new Notification();
        $notification
            ->setRecipient($connection->getRequester())
            ->setActor($viewer)
            ->setType($status === Connection::STATUS_ACCEPTED ? 'friend_request_accepted' : 'friend_request_rejected')
            ->setResourceType('connection')
            ->setResourceId($connection->getId());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return [
            'connection' => $this->formatConnection($viewer, $connection),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function formatConnection(User $viewer, Connection $connection): array
    {
        $requester = $connection->getRequester();
        $addressee = $connection->getAddressee();

        $counterparty = $requester->getId()->equals($viewer->getId()) ? $addressee : $requester;

        return [
            'id' => $connection->getId()->toRfc4122(),
            'status' => $connection->getStatus(),
            'listKey' => $connection->getListKey(),
            'counterparty' => $this->formatter->user($counterparty),
            'requestedByMe' => $requester->getId()->equals($viewer->getId()),
            'createdAt' => $connection->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $connection->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function findConnectionById(string $connectionId): ?Connection
    {
        return $this->getConnectionRepository()->find($connectionId);
    }

    private function getConnectionRepository(): ConnectionRepository
    {
        $repository = $this->entityManager->getRepository(Connection::class);
        if (!$repository instanceof ConnectionRepository) {
            throw new \LogicException('Connection repository is not configured correctly.');
        }

        return $repository;
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

