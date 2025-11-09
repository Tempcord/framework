<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;

#[Command(description: 'test')]
class CommandWithTwoSubcommands
{
    #[Subcommand]
    public function subcommand(): void
    {

    }

    #[Subcommand]
    public function subcommandTwo(): void
    {

    }
}