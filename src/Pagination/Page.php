<?php

namespace Tempcord\Pagination;

use InvalidArgumentException;

/**
 * One normalized result from a paginator, including the navigation metadata
 * that Discord needs to draw the buttons without another query.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $number,
        public int $totalPages,
        public int $totalItems,
        public int $itemsPerPage,
    ) {
        if ($number < 1 || $totalPages < 1 || $number > $totalPages || $totalItems < 0 || $itemsPerPage < 1) {
            throw new InvalidArgumentException('A pagination page has invalid bounds.');
        }
    }

    public function hasPrevious(): bool
    {
        return $this->number > 1;
    }

    public function hasNext(): bool
    {
        return $this->number < $this->totalPages;
    }
}
