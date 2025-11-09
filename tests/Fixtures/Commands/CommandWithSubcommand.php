<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;

#[Command(description: 'test')]
class CommandWithSubcommand
{
    #[Subcommand]
    public function subcommand(): void
    {

    }
}