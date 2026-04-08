<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Messenger;

use CoreBundle\Entity\Messenger\Conversation;
use CoreBundle\Entity\Messenger\Message;
use CoreBundle\Entity\Messenger\MessageReceipt;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReceipt>
 */
class MessageReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReceipt::class);
    }

    /**
     * @return list<MessageReceipt>
     */
    public function findByMessage(Message $message): array
    {
        return $this->createQueryBuilder('receipt')
            ->innerJoin('receipt.recipient', 'recipient')->addSelect('recipient')
            ->where('receipt.message = :message')
            ->setParameter('message', $message)
            ->orderBy('receipt.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MessageReceipt>
     */
    public function findPendingDeliveryForConversationAndUser(Conversation $conversation, User $user): array
    {
        return $this->createQueryBuilder('receipt')
            ->innerJoin('receipt.message', 'message')->addSelect('message')
            ->where('message.conversation = :conversation')
            ->andWhere('receipt.recipient = :user')
            ->andWhere('receipt.deliveredAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MessageReceipt>
     */
    public function findUnreadForConversationAndUser(Conversation $conversation, User $user): array
    {
        return $this->createQueryBuilder('receipt')
            ->innerJoin('receipt.message', 'message')->addSelect('message')
            ->where('message.conversation = :conversation')
            ->andWhere('receipt.recipient = :user')
            ->andWhere('receipt.readAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

