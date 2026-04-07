<?php

namespace CoreBundle\Util\Pagination;

/**
 * Class PaginatedCollection
 */
class PaginatedCollection
{
    protected array $data;

    public function __construct(array $items, int $totalItems)
    {
        $this->data['items'] = $items;
        $this->data['total'] = $totalItems;
        $this->data['count'] = \count($items);
    }

    public function addPagination(string $rel, int $page)
    {
        $this->data['pagination'][$rel] = $page;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
