<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;

#[Command(description: 'Guarded at every level', middleware: [OuterMiddleware::class])]
final class GuardedCommand
{
    /** @var list<string> */
    public static array $calls = [];

    #[Subcommand(name: 'open', description: 'Guarded by the command alone')]
    public function open(): void
    {
        self::$calls[] = 'open';
    }

    #[Subcommand(name: 'shut', description: 'Guarded again', middleware: [InnerMiddleware::class])]
    public function shut(): void
    {
        self::$calls[] = 'shut';
    }
}
