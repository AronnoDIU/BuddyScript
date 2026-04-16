<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Community;

use CoreBundle\Entity\Community\Event;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findAccessibleForUser(string $id, User $user): ?Event
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('event')
            ->where('event.id = :id')
            ->andWhere('event.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\EventMembership em
                WHERE em.event = event.id AND em.user = :user
            )')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Event>
     */
    public function findEventsForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('event')
            ->distinct()
            ->leftJoin('event.creator', 'creator')->addSelect('creator')
            ->innerJoin('event.memberships', 'membership')
            ->leftJoin('event.posts', 'post')->addSelect('post')
            ->where('membership.user = :user')
            ->setParameter('user', $user)
            ->orderBy('membership.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Event>
     */
    public function findUpcomingEvents(int $limit = 50): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('event')
            ->distinct()
            ->leftJoin('event.creator', 'creator')->addSelect('creator')
            ->leftJoin('event.memberships', 'membership')->addSelect('membership')
            ->leftJoin('event.posts', 'post')->addSelect('post')
            ->where('event.startDate > :now')
            ->andWhere('event.status = :status')
            ->setParameter('now', $now)
            ->setParameter('status', Event::STATUS_UPCOMING)
            ->orderBy('event.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Event>
     */
    public function searchEvents(string $query, User $user, int $limit = 20): array
    {
        $search = '%' . mb_strtolower(trim($query)) . '%';

        return $this->createQueryBuilder('event')
            ->distinct()
            ->leftJoin('event.creator', 'creator')->addSelect('creator')
            ->leftJoin('event.memberships', 'membership')->addSelect('membership')
            ->leftJoin('event.posts', 'post')->addSelect('post')
            ->where('LOWER(event.name) LIKE :search OR LOWER(event.description) LIKE :search')
            ->andWhere('event.creator = :user OR EXISTS (
                SELECT 1 FROM CoreBundle\Entity\Community\EventMembership em
                WHERE em.event = event.id AND em.user = :user
            )')
            ->setParameter('search', $search)
            ->setParameter('user', $user)
            ->orderBy('event.startDate', 'ASC')
            ->addOrderBy('event.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Event>
     */
    public function findEventsByCreator(User $creator, int $limit = 20): array
    {
        return $this->createQueryBuilder('event')
            ->where('event.creator = :creator')
            ->setParameter('creator', $creator)
            ->orderBy('event.startDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Event>
     */
    public function findPastEvents(int $limit = 20): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('event')
            ->where('event.endDate < :now')
            ->setParameter('now', $now)
            ->orderBy('event.endDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{eventsCount:int, createdEventsCount:int, upcomingEventsCount:int}
     */
    public function buildEventStatsForUser(User $user): array
    {
        $membershipQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Event::class, 'e')
            ->innerJoin('e.memberships', 'em')
            ->where('em.user = :user')
            ->setParameter('user', $user);

        $createdQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Event::class, 'e')
            ->where('e.creator = :user')
            ->setParameter('user', $user);

        $upcomingQb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Event::class, 'e')
            ->innerJoin('e.memberships', 'em')
            ->where('em.user = :user')
            ->andWhere('e.startDate > :now')
            ->andWhere('e.status = :status')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', Event::STATUS_UPCOMING);

        return [
            'eventsCount' => (int) $membershipQb->getQuery()->getSingleScalarResult(),
            'createdEventsCount' => (int) $createdQb->getQuery()->getSingleScalarResult(),
            'upcomingEventsCount' => (int) $upcomingQb->getQuery()->getSingleScalarResult(),
        ];
    }
}
