<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Messenger;

use CoreBundle\Entity\Messenger\Conversation;
use CoreBundle\Entity\Messenger\ConversationParticipant;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConversationParticipant>
 */
class ConversationParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConversationParticipant::class);
    }

    public function findOneByConversationAndUser(Conversation $conversation, User $user): ?ConversationParticipant
    {
        return $this->findOneBy([
            'conversation' => $conversation,
            'user' => $user,
        ]);
    }
}

