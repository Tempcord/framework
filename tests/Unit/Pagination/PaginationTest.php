<?php

namespace Tempcord\Tests\Unit\Pagination;

use PHPUnit\Framework\Attributes\CoversClass;
use React\Promise\PromiseInterface;
use Tempcord\Pagination\ArrayPaginator;
use Tempcord\Pagination\Page;
use Tempcord\Pagination\Pagination;
use Tempcord\Pagination\Paginator;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Reflection\ClassReflector;

use function React\Async\async;
use function React\Async\await;
use function React\Promise\resolve;

#[CoversClass(ArrayPaginator::class)]
#[CoversClass(Page::class)]
#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public function test_array_paginator_clamps_out_of_range_pages(): void
    {
        $paginator = new ArrayPaginator(['one', 'two', 'three'], itemsPerPage: 2);

        $page = $paginator->page(99);

        $this->assertSame(['three'], $page->items);
        $this->assertSame(2, $page->number);
        $this->assertSame(2, $page->totalPages);
        $this->assertTrue($page->hasPrevious());
        $this->assertFalse($page->hasNext());
    }

    public function test_it_builds_disabled_edges_and_page_button_ids(): void
    {
        $response = (new Pagination())->reply(
            new ArrayPaginator(['one', 'two', 'three'], itemsPerPage: 2),
            static fn(Page $page): string => implode(', ', $page->items),
        )->get();

        $this->assertSame('one, two', $response['data']['content']);
        $buttons = $response['data']['components'][0]['components'];

        $this->assertCount(5, $buttons);
        $this->assertTrue($buttons[0]['disabled']);
        $this->assertTrue($buttons[1]['disabled']);
        $this->assertSame('1 / 2', $buttons[2]['label']);
        $this->assertFalse($buttons[3]['disabled']);
        $this->assertMatchesRegularExpression('/^pagination\.[a-f0-9]{12}\.2$/', $buttons[3]['custom_id']);
    }

    public function test_a_custom_async_paginator_receives_the_requested_page(): void
    {
        $paginator = new class implements Paginator {
            public int $requested = 0;

            public function page(int $number): PromiseInterface
            {
                $this->requested = $number;

                return resolve(new Page(['custom'], $number, 3, 3, 1));
            }
        };

        $response = await(async(fn() => (new Pagination())->reply(
            $paginator,
            static fn(Page $page): string => 'page ' . $page->number,
        ))());

        $this->assertSame(1, $paginator->requested);
        $this->assertSame('page 1', $response->get()['data']['content']);
    }

    public function test_the_page_route_is_a_framework_component_handler(): void
    {
        $definitions = (new ComponentCompiler())->compile(new ClassReflector(Pagination::class));

        $this->assertCount(1, $definitions);
        $this->assertSame('pagination.{session}.{page}', $definitions[0]->customId->pattern);
    }
}
