<?php

namespace Tempcord\Pagination;

use React\Promise\PromiseInterface;

/**
 * A source of pages. Custom sources may read a remote API asynchronously;
 * ArrayPaginator and DatabasePaginator are the standard local adapters.
 */
interface Paginator
{
    /**
     * @return Page|PromiseInterface<Page>
     */
    public function page(int $number): Page|PromiseInterface;
}
