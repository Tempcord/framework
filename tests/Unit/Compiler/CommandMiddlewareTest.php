<?php

namespace Tempcord\Tests\Unit\Compiler;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Middleware\RequiresPermissions;
use Tempcord\Tests\Fixtures\GuardedCommand;
use Tempcord\Tests\Fixtures\GuardedGroupCommand;
use Tempcord\Tests\Fixtures\InlineGuardedCommand;
use Tempcord\Tests\Fixtures\InnerMiddleware;
use Tempcord\Tests\Fixtures\NotMiddlewareCommand;
use Tempcord\Tests\Fixtures\OuterMiddleware;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Fixtures\RefusingMiddleware;
use Tempcord\Tests\Unit\TestCase;

/**
 * Middleware is declared around a handler at up to three levels and reaches it
 * flattened into one list, so that nothing downstream has to walk the tree
 * again to work out what guards what.
 */
#[CoversClass(CommandCompiler::class)]
final class CommandMiddlewareTest extends TestCase
{
    public function test_a_command_passes_its_middleware_to_every_handler_under_it(): void
    {
        $handlers = $this->definition(GuardedCommand::class)->handlers;

        $this->assertSame([OuterMiddleware::class], $handlers['guarded.open']->middleware);
    }

    public function test_a_subcommands_own_middleware_runs_inside_the_commands(): void
    {
        $handlers = $this->definition(GuardedCommand::class)->handlers;

        $this->assertSame(
            [OuterMiddleware::class, InnerMiddleware::class],
            $handlers['guarded.shut']->middleware,
        );
    }

    public function test_a_group_sits_between_the_command_and_the_subcommand(): void
    {
        $handler = $this->definition(GuardedGroupCommand::class)->handlers['guarded_group.keys.cut'];

        $this->assertSame(
            [OuterMiddleware::class, InnerMiddleware::class, RefusingMiddleware::class],
            $handler->middleware,
        );
    }

    public function test_middleware_written_inline_is_kept_as_the_object_it_is(): void
    {
        $middleware = $this->definition(InlineGuardedCommand::class)->handlers['inline_guarded']->middleware;

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(RequiresPermissions::class, $middleware[0]);
        $this->assertSame('Not for you.', $middleware[0]->refusal);
    }

    public function test_a_command_declaring_none_carries_none(): void
    {
        $this->assertSame([], $this->definition(PingCommand::class)->handlers['ping']->middleware);
    }

    /**
     * The check belongs at discovery because that is start-up: a guard that is
     * not a guard should stop the bot booting, not surface the first time
     * somebody uses the command it was supposed to protect.
     */
    public function test_a_class_that_is_not_middleware_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('does not implement');

        $this->definition(NotMiddlewareCommand::class);
    }
}
