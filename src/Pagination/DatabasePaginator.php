<?php

namespace Tempcord\Pagination;

use Tempest\Database\Builder\QueryBuilders\SelectQueryBuilder;

/**
 * Pages a Tempest database query lazily: a button press reads only the page it
 * opened, rather than loading an entire result set into the Discord process.
 *
 * @template T of object
 */
final readonly class DatabasePaginator implements Paginator
{
    /** @param SelectQueryBuilder<T> $query */
    public function __construct(
        private SelectQueryBuilder $query,
        private int $itemsPerPage = 5,
    ) {}

    public function page(int $number): Page
    {
        $data = $this->query->paginate(itemsPerPage: $this->itemsPerPage, currentPage: max(1, $number));

        return new Page(
            items: $data->data,
            number: $data->currentPage,
            totalPages: $data->totalPages,
            totalItems: $data->totalItems,
            itemsPerPage: $data->itemsPerPage,
        );
    }
}
