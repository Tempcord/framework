<?php

namespace Tempcord\Pagination;

use InvalidArgumentException;

/** @template T */
final readonly class ArrayPaginator implements Paginator
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        private array $items,
        private int $itemsPerPage = 5,
    ) {
        if ($itemsPerPage < 1) {
            throw new InvalidArgumentException('Items per page must be at least one.');
        }
    }

    public function page(int $number): Page
    {
        $totalItems = count($this->items);
        $totalPages = max(1, (int) ceil($totalItems / $this->itemsPerPage));
        $number = min(max(1, $number), $totalPages);

        return new Page(
            items: array_slice($this->items, ($number - 1) * $this->itemsPerPage, $this->itemsPerPage),
            number: $number,
            totalPages: $totalPages,
            totalItems: $totalItems,
            itemsPerPage: $this->itemsPerPage,
        );
    }
}
