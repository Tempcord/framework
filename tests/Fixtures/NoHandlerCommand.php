<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(description: 'Declares neither subcommands nor __invoke')]
final class NoHandlerCommand
{
    public function somethingElse(): void {}
}
