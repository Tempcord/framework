<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;

#[Command(description: 'guild test', guildId: 42)]
class GuildInvokableCommand
{
    public function __invoke(): void
    {
        // no-op handler for testing
    }
}