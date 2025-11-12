<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Subcommand;

#[Command(description: 'test')]
class CommandWithSubcommand
{
    #[Subcommand]
    public function subcommand(): void
    {

    }
}