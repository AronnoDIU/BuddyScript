<?php

declare(strict_types=1);

namespace CoreBundle\Repository;

use CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

class UserRepository extends BaseRepository
{
    public function findOneByEmailOrUsername(string $identifier): ?User
    {
        $normalized = mb_strtolower(trim($identifier));
        if ($normalized === '') {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :identifier OR LOWER(u.username) = :identifier')
            ->setParameter('identifier', $normalized)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function filter(Request $request): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if ($id = $request->query->get('id')) {
            $qb->andWhere('u.id = :id')
                ->setParameter('id', $id);
        }

        if ($userName = $request->query->get('user_name')) {
            $qb->andWhere('u.username like :userName')
                ->setParameter('userName', \sprintf('%s%s%s', '%', $userName, '%'));
        }

        if ($email = $request->query->get('email')) {
            $qb->andWhere('u.email = :email')
                ->setParameter('email', $email);
        }

        if ($phone = $request->query->get('phone')) {
            $qb->andWhere('u.phone = :phone')
                ->setParameter('phone', $phone);
        }

        $enabled = $request->query->get('enabled');
        if (null !== $enabled) {
            $qb->andWhere('u.enabled = :enabled')
                ->setParameter('enabled', $request->query->getBoolean('enabled'));
        }

        return $qb;
    }

    public function findOneById(string $id): ?User
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->where('u.id = :id')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
