<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return list<Post>
     */
    public function findFeedForUser(User $viewer, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->leftJoin('p.likes', 'pl')->addSelect('pl')
            ->leftJoin('pl.user', 'plu')->addSelect('plu')
            ->leftJoin('p.comments', 'c')->addSelect('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->leftJoin('c.likes', 'cl')->addSelect('cl')
            ->leftJoin('cl.user', 'clu')->addSelect('clu')
            ->leftJoin('c.replies', 'r')->addSelect('r')
            ->leftJoin('r.author', 'ra')->addSelect('ra')
            ->leftJoin('r.likes', 'rl')->addSelect('rl')
            ->leftJoin('rl.user', 'rlu')->addSelect('rlu')
            ->where('p.visibility = :public OR p.author = :viewer')
            ->setParameter('public', Post::VISIBILITY_PUBLIC)
            ->setParameter('viewer', $viewer)
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->addOrderBy('r.createdAt', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findAccessibleForUser(string $id, User $user): ?Post
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->createQueryBuilder('post')
            ->innerJoin('post.author', 'author')
            ->addSelect('author')
            ->where('post.id = :id')
            ->andWhere('post.visibility = :publicVisibility OR author = :viewer')
            ->setParameter('id', $uuid, UuidType::NAME)
            ->setParameter('publicVisibility', Post::VISIBILITY_PUBLIC)
            ->setParameter('viewer', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
