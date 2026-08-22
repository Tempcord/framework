<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command(name: CommandName::Weather, description: 'Named by a backed enum')]
final class EnumNamedCommand
{
    public function __invoke(): void {}
}
