<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;

#[Command(name: 'explicit', description: 'Has an explicit name')]
final class NamedCommand
{
    public function __invoke(): void {}
}
