<?php

namespace CoreBundle\Util\Pagination;

class PaginatedCollection
{
    protected array $data = [];

    public function __construct(array $items, int $totalItems)
    {
        $this->data = [
            'items' => $items,
            'total' => $totalItems,
            'count' => \count($items),
        ];
    }

    public function addPagination(string $rel, int $page): void
    {
        $data = $this->data;
        $data['pagination'][$rel] = $page;
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

}
