<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Messenger;

use CoreBundle\Entity\Messenger\Conversation;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * @return list<Conversation>
     */
    public function findForUser(User $user, string $query = '', int $limit = 50, int $offset = 0, bool $includeArchived = false): array
    {
        $viewerId = $user->getId();

        $qb = $this->createQueryBuilder('conversation')
            ->innerJoin('conversation.participants', 'mine')->addSelect('mine')
            ->innerJoin('conversation.participants', 'participant')->addSelect('participant')
            ->innerJoin('participant.user', 'participantUser')->addSelect('participantUser')
            ->leftJoin('conversation.messages', 'message')->addSelect('message')
            ->leftJoin('message.sender', 'sender')->addSelect('sender')
            ->leftJoin('message.attachments', 'attachment')->addSelect('attachment')
            ->where('IDENTITY(mine.user) = :viewerId')
            ->setParameter('viewerId', $viewerId, UuidType::NAME)
            ->addOrderBy('mine.isPinned', 'DESC')
            ->addOrderBy('conversation.lastMessageAt', 'DESC')
            ->addOrderBy('conversation.updatedAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit));

        if (!$includeArchived) {
            $qb->andWhere('mine.archivedAt IS NULL');
        }

        $normalizedQuery = trim($query);
        if ($normalizedQuery !== '') {
            $search = '%' . mb_strtolower($normalizedQuery) . '%';
            $qb
                ->andWhere('IDENTITY(participant.user) != :viewerId')
                ->andWhere('LOWER(participantUser.email) LIKE :search OR LOWER(CONCAT(CONCAT(participantUser.firstName, :space), participantUser.lastName)) LIKE :search')
                ->setParameter('search', $search)
                ->setParameter('space', ' ');
        }

        return $qb->getQuery()->getResult();
    }

    public function findForUserById(User $user, string $conversationId): ?Conversation
    {
        try {
            $conversationUuid = Uuid::fromString($conversationId);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('conversation')
            ->innerJoin('conversation.participants', 'mine')->addSelect('mine')
            ->innerJoin('conversation.participants', 'participant')->addSelect('participant')
            ->innerJoin('participant.user', 'participantUser')->addSelect('participantUser')
            ->where('conversation.id = :conversationId')
            ->andWhere('IDENTITY(mine.user) = :viewerId')
            ->setParameter('conversationId', $conversationUuid, UuidType::NAME)
            ->setParameter('viewerId', $user->getId(), UuidType::NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDirectOneToOne(User $a, User $b): ?Conversation
    {
        return $this->createQueryBuilder('conversation')
            ->innerJoin('conversation.participants', 'participant')
            ->where('participant.user IN (:users)')
            ->setParameter('users', [$a, $b])
            ->groupBy('conversation.id')
            ->having('COUNT(participant.id) = 2')
            ->andHaving('COUNT(DISTINCT participant.user) = 2')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

