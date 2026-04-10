<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Event;
use CoreBundle\Entity\Community\EventMembership;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<EventMembership>
 */
class EventMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventMembership::class);
    }

    public function findByUserAndEvent(User $user, Event $event): ?EventMembership
    {
        return $this->createQueryBuilder('membership')
            ->where('membership.user = :user')
            ->andWhere('membership.event = :event')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<EventMembership>
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.event', 'event')
            ->addSelect('event')
            ->where('membership.user = :user')
            ->setParameter('user', $user)
            ->orderBy('membership.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<EventMembership>
     */
    public function findByEvent(Event $event, int $limit = 50): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->addSelect('user')
            ->where('membership.event = :event')
            ->setParameter('event', $event)
            ->orderBy('membership.role', 'ASC')
            ->addOrderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<EventMembership>
     */
    public function findByRole(Event $event, string $role, int $limit = 20): array
    {
        return $this->createQueryBuilder('membership')
            ->innerJoin('membership.user', 'user')
            ->addSelect('user')
            ->where('membership.event = :event')
            ->andWhere('membership.role = :role')
            ->setParameter('event', $event)
            ->setParameter('role', $role)
            ->orderBy('membership.joinedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countMembersByRole(Event $event, string $role): int
    {
        return (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->where('membership.event = :event')
            ->andWhere('membership.role = :role')
            ->setParameter('event', $event)
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{organizers:int, coorganizers:int, speakers:int, attendees:int}
     */
    public function getMemberStats(Event $event): array
    {
        $qb = $this->createQueryBuilder('membership')
            ->select('membership.role, COUNT(membership.id) as count')
            ->where('membership.event = :event')
            ->setParameter('event', $event)
            ->groupBy('membership.role');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'organizers' => 0,
            'coorganizers' => 0,
            'speakers' => 0,
            'attendees' => 0,
        ];

        foreach ($results as $result) {
            $role = $result['role'];
            $count = (int) $result['count'];

            match ($role) {
                Event::ROLE_ORGANIZER => $stats['organizers'] = $count,
                Event::ROLE_COORGANIZER => $stats['coorganizers'] = $count,
                Event::ROLE_SPEAKER => $stats['speakers'] = $count,
                Event::ROLE_ATTENDEE => $stats['attendees'] = $count,
                default => null,
            };
        }

        return $stats;
    }
}
