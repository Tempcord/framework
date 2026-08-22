<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(name: 'explicit', description: 'Has an explicit name')]
final class NamedCommand
{
    public function __invoke(): void {}
}
