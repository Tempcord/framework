<?php

namespace Tempcord\Pagination;

use Closure;

/** @internal */
final readonly class PaginationSession
{
    /**
     * @param Closure(Page): (string|\Tempcord\Discord\Rest\Helpers\Channel\EmbedBuilder|\React\Promise\PromiseInterface) $render
     */
    public function __construct(
        public Paginator $paginator,
        public Closure $render,
        public int $expiresAt,
    ) {}
}
