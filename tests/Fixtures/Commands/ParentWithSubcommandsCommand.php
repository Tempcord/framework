<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Subcommand;

#[Command(description: 'Parent command that only has subcommands and no own handler')]
class ParentWithSubcommandsCommand
{
    public static bool $invoked = false;

    #[Subcommand(name: 'run', description: 'Execute the run subcommand')]
    public function run(): void
    {
        // Mark invoked to detect unintended handler invocation in tests
        self::$invoked = true;
    }
}