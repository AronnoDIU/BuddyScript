<?php

declare(strict_types=1);

namespace CoreBundle\Repository\Safety;

use CoreBundle\Entity\Safety\Report;
use CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * @return list<Report>
     */
    public function findByReporter(User $reporter, int $limit = 50): array
    {
        return $this->createQueryBuilder('report')
            ->where('report.reporter = :reporter')
            ->setParameter('reporter', $reporter)
            ->orderBy('report.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

