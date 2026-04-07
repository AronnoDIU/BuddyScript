<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Auth;

use CoreBundle\Entity\Auth\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findActiveByTokenHash(string $tokenHash, \DateTimeImmutable $now): ?RefreshToken
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.tokenHash = :tokenHash')
            ->andWhere('rt.revokedAt IS NULL')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByTokenHash(string $tokenHash): ?RefreshToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }
}

