<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Auth;

use CoreBundle\Entity\Auth\TwoFactorChallenge;
use CoreBundle\Repository\BaseRepository;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class TwoFactorChallengeRepository extends BaseRepository
{
    public function findActiveById(string $id, \DateTimeImmutable $now): ?TwoFactorChallenge
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\Throwable) {
            return null;
        }

        return $this->createQueryBuilder('challenge')
            ->where('challenge.id = :id')
            ->andWhere('challenge.consumedAt IS NULL')
            ->andWhere('challenge.expiresAt >= :now')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
