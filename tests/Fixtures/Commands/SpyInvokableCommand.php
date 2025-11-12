<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;

#[Command(description: 'spy invokable')]
class SpyInvokableCommand
{
    public static bool $invoked = false;

    public function __invoke($interaction = null): void
    {
        self::$invoked = true;
    }
}