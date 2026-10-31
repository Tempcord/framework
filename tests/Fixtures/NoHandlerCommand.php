<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;

#[Command(description: 'Declares neither subcommands nor __invoke')]
final class NoHandlerCommand
{
    public function somethingElse(): void {}
}
