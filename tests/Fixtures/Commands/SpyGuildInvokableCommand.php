<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;

#[Command(description: 'spy guild invokable', guildId: 42)]
class SpyGuildInvokableCommand
{
    public static bool $invoked = false;

    public function __invoke($interaction = null): void
    {
        self::$invoked = true;
    }
}