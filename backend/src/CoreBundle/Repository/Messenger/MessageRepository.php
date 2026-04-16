<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Messenger;

use CoreBundle\Entity\Messenger\Conversation;
use CoreBundle\Entity\Messenger\Message;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return list<Message>
     */
    public function findForConversation(Conversation $conversation, int $limit = 100, ?\DateTimeImmutable $before = null): array
    {
        $conversationId = $conversation->getId();

        $qb = $this->createQueryBuilder('message')
            ->distinct()
            ->leftJoin('message.sender', 'sender')->addSelect('sender')
            ->leftJoin('message.attachments', 'attachment')->addSelect('attachment')
            ->leftJoin('message.receipts', 'receipt')->addSelect('receipt')
            ->leftJoin('receipt.recipient', 'recipient')->addSelect('recipient')
            ->where('IDENTITY(message.conversation) = :conversationId')
            ->setParameter('conversationId', $conversationId, UuidType::NAME)
            ->setMaxResults($limit);

        if ($before instanceof \DateTimeImmutable) {
            $qb
                ->andWhere('message.createdAt < :before')
                ->setParameter('before', $before)
                ->orderBy('message.createdAt', 'DESC');

            return array_reverse($qb->getQuery()->getResult());
        }

        return $qb
            ->orderBy('message.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countOlderThan(Conversation $conversation, \DateTimeImmutable $before): int
    {
        return (int) $this->createQueryBuilder('message')
            ->select('COUNT(message.id)')
            ->where('IDENTITY(message.conversation) = :conversationId')
            ->andWhere('message.createdAt < :before')
            ->setParameter('conversationId', $conversation->getId(), UuidType::NAME)
            ->setParameter('before', $before)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLatestForConversation(Conversation $conversation): ?Message
    {
        return $this->createQueryBuilder('message')
            ->leftJoin('message.sender', 'sender')->addSelect('sender')
            ->leftJoin('message.attachments', 'attachment')->addSelect('attachment')
            ->leftJoin('message.receipts', 'receipt')->addSelect('receipt')
            ->leftJoin('receipt.recipient', 'recipient')->addSelect('recipient')
            ->where('IDENTITY(message.conversation) = :conversationId')
            ->setParameter('conversationId', $conversation->getId(), UuidType::NAME)
            ->orderBy('message.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countUnreadForUser(Conversation $conversation, User $viewer, ?\DateTimeImmutable $lastReadAt): int
    {
        $qb = $this->createQueryBuilder('message')
            ->select('COUNT(message.id)')
            ->where('IDENTITY(message.conversation) = :conversationId')
            ->andWhere('message.sender != :viewer')
            ->setParameter('conversationId', $conversation->getId(), UuidType::NAME)
            ->setParameter('viewer', $viewer);

        if ($lastReadAt instanceof \DateTimeImmutable) {
            $qb->andWhere('message.createdAt > :lastReadAt')->setParameter('lastReadAt', $lastReadAt);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Message>
     */
    public function findRecentForUser(User $viewer, ?\DateTimeImmutable $since = null, int $limit = 80): array
    {
        $qb = $this->createQueryBuilder('message')
            ->innerJoin('message.conversation', 'conversation')
            ->innerJoin('conversation.participants', 'participant')
            ->leftJoin('message.sender', 'sender')->addSelect('sender')
            ->leftJoin('message.attachments', 'attachment')->addSelect('attachment')
            ->leftJoin('message.receipts', 'receipt')->addSelect('receipt')
            ->leftJoin('receipt.recipient', 'recipient')->addSelect('recipient')
            ->where('participant.user = :viewer')
            ->setParameter('viewer', $viewer)
            ->orderBy('message.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($since instanceof \DateTimeImmutable) {
            $qb->andWhere('message.createdAt > :since')->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }
}

