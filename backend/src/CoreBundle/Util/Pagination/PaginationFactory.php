<?php

namespace CoreBundle\Util\Pagination;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;

class PaginationFactory
{
    public function createCollection(QueryBuilder $qb, Request $request): PaginatedCollection
    {
        $page = $request->request->get('page') ?? $request->query->get('page') ?? 1;
        $limit = $request->request->get('limit') ?? $request->query->get('limit') ?? 10;

        $pagerfanta = new Pagerfanta(
            new QueryAdapter($qb, false, false)
        );

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        $items = [];
        foreach ($pagerfanta->getCurrentPageResults() as $result) {
            $items[] = $result;
        }

        $paginatedCollection = new PaginatedCollection($items, $pagerfanta->getNbResults());
        $paginatedCollection->addPagination('self', $page);
        $paginatedCollection->addPagination('first', 1);
        $paginatedCollection->addPagination('last', $pagerfanta->getNbPages());

        if ($pagerfanta->hasNextPage()) {
            $paginatedCollection->addPagination('next', $pagerfanta->getNextPage());
        }

        if ($pagerfanta->hasPreviousPage()) {
            $paginatedCollection->addPagination('prev', $pagerfanta->getPreviousPage());
        }

        return $paginatedCollection;
    }
}
