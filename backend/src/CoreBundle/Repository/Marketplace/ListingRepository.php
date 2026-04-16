<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Marketplace;

use CoreBundle\Entity\Marketplace\Listing;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Listing>
 */
class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    public function findById(string $id): ?Listing
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\Throwable) {
            return null;
        }

        return $this->createQueryBuilder('listing')
            ->innerJoin('listing.seller', 'seller')
            ->addSelect('seller')
            ->where('listing.id = :id')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Listing>
     */
    public function findMarketplaceFeed(string $query, ?string $category, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('listing')
            ->innerJoin('listing.seller', 'seller')
            ->addSelect('seller')
            ->where('listing.status = :status')
            ->setParameter('status', Listing::STATUS_ACTIVE)
            ->orderBy('listing.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $normalized = mb_strtolower(trim($query));
        if ($normalized !== '') {
            $search = '%' . $normalized . '%';
            $qb
                ->andWhere('LOWER(listing.title) LIKE :search OR LOWER(listing.description) LIKE :search OR LOWER(listing.location) LIKE :search')
                ->setParameter('search', $search);
        }

        if ($category !== null && $category !== '') {
            $qb
                ->andWhere('listing.category = :category')
                ->setParameter('category', mb_strtolower(trim($category)));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Listing>
     */
    public function findBySeller(User $seller, int $limit = 50): array
    {
        return $this->createQueryBuilder('listing')
            ->where('listing.seller = :seller')
            ->setParameter('seller', $seller)
            ->orderBy('listing.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

